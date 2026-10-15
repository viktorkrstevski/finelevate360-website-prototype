/* Finelevate360 — AI Readiness Assessment v2 (prototype engine)
   Flow: intro → profile → questions (6 dimensions, maturity scale) → result (ungated) → detailed report (email + consent) → thank you.
   Questions adapt to the audience: financial institutions get EU AI Act / DORA / model-risk items; companies get general governance items.
   Scoring: each answer 0–3 (Not started 0, Exploring 1, In progress 2, Established 3); "Not sure" scores 0 and is reported separately.
   Dimension score = points / (3 × answered-or-not-sure items) × 100. Overall = mean of dimension scores. Levels: 1 <25, 2 <45, 3 <65, 4 <85, 5 ≥85.
   State is saved in localStorage so a visitor can resume. In WordPress this file becomes the plugin's front-end; submit() posts to the REST endpoint. */
(function () {
  "use strict";
  var KEY = "fe360_assessment_v2";
  var SCALE = [
    { v: 0, label: "Not started", hint: "Nothing in place yet" },
    { v: 1, label: "Exploring", hint: "Discussed or piloted informally" },
    { v: 2, label: "In progress", hint: "Partly in place, not yet consistent" },
    { v: 3, label: "Established", hint: "In place, owned and working" }
  ];
  var DIMS = [
    { id: "strategy", name: "Strategy & leadership", blurb: "A clear AI ambition, owned at the top and tied to business value." },
    { id: "usecases", name: "Use cases & value", blurb: "The right first use cases, with value you can measure." },
    { id: "data", name: "Data & governance", blurb: "Data that is owned, findable, good enough and lawfully used." },
    { id: "tech", name: "Technology & delivery", blurb: "Platforms, integration and partners that can take AI to production." },
    { id: "people", name: "People & skills", blurb: "People who can use, build and challenge AI responsibly." },
    { id: "risk", name: "Risk, compliance & trust", blurb: "Controls that let AI pass the risk committee and the regulator." }
  ];
  // q: text; d: dimension; fi: only financial institutions; co: only companies
  var Q = [
    { d: "strategy", t: "Leadership has agreed what AI should achieve for the organisation in the next two years." },
    { d: "strategy", t: "A named executive owns the AI agenda and has a budget for it." },
    { d: "strategy", t: "AI initiatives are prioritised against business value, not novelty." },
    { d: "usecases", t: "We have a short list of AI use cases ranked by value and feasibility." },
    { d: "usecases", t: "At least one AI use case is used in daily work, not only in a pilot." },
    { d: "usecases", t: "We measure the benefit of AI use cases against a baseline." },
    { d: "data", t: "Each critical data domain (customers, transactions, products) has an accountable owner." },
    { d: "data", t: "Our core systems share identifiers, so data can be joined reliably." },
    { d: "data", t: "We measure and manage the quality of the data AI will depend on." },
    { d: "data", t: "Personal data is classified, access-controlled and used on a recorded lawful basis (GDPR)." },
    { d: "tech", t: "Our core systems can expose data and services through modern interfaces (APIs)." },
    { d: "tech", t: "We have a secure environment where AI solutions can be built, tested and monitored." },
    { d: "tech", t: "We know which partners we would use to build and run AI solutions." },
    { d: "people", t: "Staff who will use AI have been trained to use it well and to question its output." },
    { d: "people", t: "We have — or can access — people who can build and validate AI models." },
    { d: "people", t: "Business, IT and risk teams work on AI together from the start." },
    { d: "risk", t: "We keep an inventory of where AI or automated decision-making is used or piloted." },
    { d: "risk", fi: true, t: "We have classified which use cases are high-risk under the EU AI Act (for example credit scoring or insurance pricing)." },
    { d: "risk", fi: true, t: "AI and its third-party providers are covered by our ICT-risk and outsourcing controls (DORA, National Bank rules)." },
    { d: "risk", fi: true, t: "AI models that inform decisions are validated, monitored and explainable to a supervisor." },
    { d: "risk", co: true, t: "We have simple rules for the responsible use of AI tools, including what data may be shared." },
    { d: "risk", co: true, t: "We review AI output before it reaches customers or important decisions." },
    { d: "risk", t: "A named person is accountable for AI risk and human oversight of AI decisions." }
  ];
  var LEVELS = [
    { min: 0, n: 1, name: "Initial", head: "The journey starts here.", lead: "Curiosity is there; the foundations are not yet. Start small, with one use case and clear ownership." },
    { min: 25, n: 2, name: "Developing", head: "Promising, but on shaky foundations.", lead: "Enthusiasm is ahead of capability. A short readiness sprint on data and ownership will pay back quickly." },
    { min: 45, n: 3, name: "Defined", head: "Real foundations, real gaps.", lead: "You are ready for a first production use case — if you close the gaps that a risk committee would see." },
    { min: 65, n: 4, name: "Advanced", head: "A strong, structured position.", lead: "Scale what works, prove the value, and keep the evidence that regulators will ask for." },
    { min: 85, n: 5, name: "Leading", head: "AI is part of how you work.", lead: "Keep improving continuously — and share what you know with the ecosystem." }
  ];
  var ACTIONS = {
    strategy: ["Name one executive owner for AI and agree a one-page ambition.", "Link each AI initiative to a business objective and a budget line.", "Review the AI portfolio twice a year against value delivered."],
    usecases: ["List five candidate use cases and score them for value and feasibility.", "Take one use case from pilot into daily work, with a measured baseline.", "Build a repeatable path from idea to production."],
    data: ["Name owners for your three most critical data domains.", "Run a quick data-quality check on the data your first use case needs.", "Put a catalogue and quality monitoring in place for AI-critical data."],
    tech: ["Map which systems your first use case must connect to.", "Agree a secure build-and-test environment and a delivery partner.", "Standardise monitoring and deployment for AI solutions."],
    people: ["Run a two-hour AI awareness session for the people who will use it.", "Form a small cross-functional team: business, IT, risk.", "Plan role-based training and access to modelling expertise."],
    risk: ["Start an inventory of every AI and automated-decision use.", "Classify use cases against the EU AI Act and assign an accountable owner.", "Embed validation, monitoring and human oversight in your control framework."]
  };
  var ORG = [
    { v: "bank", l: "Bank", fi: true }, { v: "insurer", l: "Insurer", fi: true }, { v: "payments", l: "Payments or fintech", fi: true },
    { v: "otherfi", l: "Other financial institution (leasing, microfinance, savings house)", fi: true },
    { v: "company", l: "Company outside financial services", fi: false }, { v: "public", l: "Public institution", fi: false }
  ];
  var SIZE = ["Under 50 people", "50–249", "250–999", "1,000 or more"];
  var COUNTRY = ["North Macedonia", "Serbia", "Croatia", "Montenegro", "Slovenia", "Albania", "Kosovo", "Bosnia and Herzegovina", "Bulgaria", "Other"];
  var ROLE = ["Board or executive", "Risk, compliance or audit", "IT, data or digital", "Business or operations", "Other"];

  var root = document.getElementById("assess");
  var st = load() || { step: "intro", profile: {}, answers: {}, i: 0, lead: {} };
  // Demo states for reviews and screenshots: #demo-profile, #demo-question, #demo-result, #demo-report, #demo-thanks
  var demo = (location.hash.match(/^#demo-(\w+)/) || [])[1];
  if (demo) {
    var sample = [3, 2, 3, 2, 1, 1, 1, 0, 1, "ns", 2, 1, 2, 2, 1, 2, 1, 1, "ns", 1, 2];
    st = { step: "intro", profile: { org: "bank", size: "250–999", country: "North Macedonia", role: "Risk, compliance or audit" }, answers: {}, i: 0, lead: { name: "Ana Petrovska", email: "ana@example.com", company: "Example Bank" } };
    if (demo === "profile") { st.step = "profile"; st.profile = {}; }
    if (demo === "question") { st.step = "q"; st.i = 8; for (var k = 0; k < 8; k++) st.answers[k] = sample[k]; }
    if (demo === "result" || demo === "report" || demo === "thanks") { sample.forEach(function (v, k) { st.answers[k] = v; }); st.step = demo; }
    save = function () {}; // never overwrite a real visitor's saved progress with demo data
  }

  function save() { try { localStorage.setItem(KEY, JSON.stringify(st)); } catch (e) {} }
  function load() { try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; } }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function isFI() { var o = ORG.filter(function (x) { return x.v === st.profile.org; })[0]; return o ? o.fi : true; }
  function questions() { var fi = isFI(); return Q.filter(function (q) { return fi ? !q.co : !q.fi; }); }

  function score() {
    var qs = questions(), per = {}, notSure = 0;
    DIMS.forEach(function (d) { per[d.id] = { pts: 0, max: 0 }; });
    qs.forEach(function (q, i) {
      var a = st.answers[i]; if (a === undefined) return;
      per[q.d].max += 3;
      if (a === "ns") notSure++; else per[q.d].pts += a;
    });
    var dims = DIMS.map(function (d) { var p = per[d.id]; return { id: d.id, name: d.name, pct: p.max ? Math.round(p.pts / p.max * 100) : 0 }; });
    var pct = Math.round(dims.reduce(function (s, d) { return s + d.pct; }, 0) / dims.length);
    var lvl = LEVELS.slice().reverse().filter(function (l) { return pct >= l.min; })[0];
    var sorted = dims.slice().sort(function (a, b) { return a.pct - b.pct; });
    return { pct: pct, level: lvl, dims: dims, weakest: sorted.slice(0, 2), strongest: sorted[sorted.length - 1], notSure: notSure, total: qs.length };
  }

  /* ---------- views ---------- */
  function shell(inner, opts) {
    opts = opts || {};
    var prog = opts.progress != null ? '<div class="a-prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + opts.progress + '"><i style="width:' + opts.progress + '%"></i></div>' : "";
    return '<div class="a-card">' + prog + '<div class="a-body">' + inner + "</div></div>";
  }

  function vIntro() {
    var dims = DIMS.map(function (d, i) { return '<li><b>' + (i + 1) + ". " + esc(d.name) + "</b><span>" + esc(d.blurb) + "</span></li>"; }).join("");
    var resume = Object.keys(st.answers).length ? '<button class="btn ghost" data-act="resume">Continue where you left off</button>' : "";
    return '<div class="a-intro">' +
      '<div><span class="eyebrow">Free · about 10 minutes · no sign-up needed</span>' +
      '<h1 class="h2" style="margin-top:14px">How ready is your organisation for AI?</h1>' +
      '<p class="lede" style="margin-top:16px">About twenty questions across six dimensions. You see your result straight away — your level, your strengths, and what to do this week, this quarter and this year.</p>' +
      '<div class="a-actions"><button class="btn" data-act="start">Start the assessment <span class="arr">→</span></button>' + resume + '</div>' +
      '<p class="a-privacy"><b>Your privacy.</b> Your answers stay in this browser until you choose to send them. If you ask for the detailed report, we use your email only for that — and for anything else you explicitly tick. Peer comparisons show groups of five or more organisations only.</p></div>' +
      '<div class="a-dims"><p class="eyebrow green" style="margin-bottom:12px">What we look at</p><ol>' + dims + "</ol></div></div>";
  }

  function sel(name, label, opts, val) {
    return '<label class="a-field"><span>' + label + '</span><select name="' + name + '" required><option value="">Choose…</option>' +
      opts.map(function (o) { var v = o.v || o, l = o.l || o; return '<option value="' + esc(v) + '"' + (val === v ? " selected" : "") + ">" + esc(l) + "</option>"; }).join("") + "</select></label>";
  }
  function vProfile() {
    var p = st.profile;
    return shell('<span class="a-step">Step 1 of 3 · About you</span><h2 class="a-q">First, a little about your organisation.</h2>' +
      '<p class="muted" style="margin-top:6px">This tailors the questions — financial institutions see regulatory questions, companies see simpler governance ones.</p>' +
      '<form class="a-form" data-form="profile">' + sel("org", "Organisation type", ORG, p.org) + sel("size", "Size", SIZE, p.size) + sel("country", "Country", COUNTRY, p.country) + sel("role", "Your role", ROLE, p.role) +
      '<div class="a-nav"><button type="button" class="a-link" data-act="intro">← Back</button><button class="btn" type="submit">Continue <span class="arr">→</span></button></div></form>', { progress: 4 });
  }

  function vQuestion() {
    var qs = questions(), q = qs[st.i], dimIdx = DIMS.map(function (d) { return d.id; }).indexOf(q.d), dim = DIMS[dimIdx];
    var cur = st.answers[st.i];
    var first = st.i === 0 || qs[st.i - 1].d !== q.d;
    var cheer = first && st.i > 0 ? '<p class="a-cheer">' + (dimIdx === 3 ? "Halfway there — thank you." : "Next: " + esc(dim.name).toLowerCase() + ".") + "</p>" : "";
    var opts = SCALE.map(function (s, k) {
      return '<button class="a-opt' + (cur === s.v ? " sel" : "") + '" data-ans="' + s.v + '"><span class="a-key">' + (k + 1) + '</span><span><b>' + s.label + "</b><small>" + s.hint + "</small></span></button>";
    }).join("") + '<button class="a-opt a-ns' + (cur === "ns" ? " sel" : "") + '" data-ans="ns"><span class="a-key">5</span><span><b>Not sure</b><small>That is useful to know too</small></span></button>';
    var pct = Math.round(6 + (st.i / qs.length) * 88);
    return shell(cheer + '<span class="a-step">Step 2 of 3 · ' + esc(dim.name) + " · Question " + (st.i + 1) + " of " + qs.length + "</span>" +
      '<h2 class="a-q">' + esc(q.t) + "</h2>" + '<div class="a-opts" role="group" aria-label="Your answer">' + opts + "</div>" +
      '<div class="a-nav"><button type="button" class="a-link" data-act="back">← Back</button><span class="muted" style="font-size:13px">Tip: use keys 1–5</span></div>', { progress: pct });
  }

  function ring(p) {
    var r = 66, c = 2 * Math.PI * r, off = c * (1 - p / 100);
    return '<svg class="a-ring" viewBox="0 0 160 160" aria-hidden="true"><defs><linearGradient id="ag" x1="0" x2="1"><stop offset="0" stop-color="#149FE3"/><stop offset="1" stop-color="#008A62"/></linearGradient></defs>' +
      '<circle cx="80" cy="80" r="' + r + '" fill="none" stroke="#EFEBE3" stroke-width="14"/><circle class="a-arc" cx="80" cy="80" r="' + r + '" fill="none" stroke="url(#ag)" stroke-width="14" stroke-linecap="round" stroke-dasharray="' + c + '" stroke-dashoffset="' + c + '" data-off="' + off + '" transform="rotate(-90 80 80)"/>' +
      '<text x="80" y="88" text-anchor="middle" font-family="Montserrat" font-weight="700" font-size="34" fill="#0F1E26">' + p + "%</text></svg>";
  }

  function vResult() {
    var s = score(), w = s.weakest;
    var bars = s.dims.map(function (d) { return '<div class="bar"><div class="row"><b>' + esc(d.name) + "</b><span>" + d.pct + '%</span></div><div class="t"><div class="f" style="width:' + d.pct + '%"></div></div></div>'; }).join("");
    var plan = ["This week", "This quarter", "This year"].map(function (when, k) {
      return '<div class="a-plan-col"><span class="eyebrow">' + when + "</span><ul>" + w.map(function (d) { return "<li>" + esc(ACTIONS[d.id][k]) + "</li>"; }).join("") + "</ul></div>";
    }).join("");
    var ns = s.notSure ? '<p class="a-flag"><b>' + s.notSure + " “Not sure” answer" + (s.notSure > 1 ? "s" : "") + ".</b> Uncertainty is itself a finding — it usually means ownership is unclear. We count these as “not started”.</p>" : "";
    return shell(
      '<span class="a-step">Step 3 of 3 · Your result</span>' +
      '<div class="a-res-top">' + ring(s.pct) + '<div><span class="band">Level ' + s.level.n + " · " + s.level.name + '</span><h2 class="a-q" style="margin-top:12px">' + s.level.head + '</h2><p class="lede" style="font-size:17px;margin-top:8px">' + s.level.lead + "</p>" +
      '<p style="margin-top:12px;font-size:15px">Strongest in <b>' + esc(s.strongest.name) + "</b>. Start with <b>" + esc(w[0].name) + "</b> and <b>" + esc(w[1].name) + "</b>.</p></div></div>" +
      '<div class="bars" style="margin-top:28px">' + bars + "</div>" + ns +
      '<div class="a-plan"><h3 class="h3">Your next steps</h3><div class="a-plan-grid">' + plan + "</div></div>" +
      '<div class="a-peer"><b>Compare with your peers — from December.</b> When at least five organisations of your type have taken part, we will show how you compare. Ask for the detailed report and you will receive it first.</div>' +
      '<div class="a-cta-row"><button class="btn" data-act="report">Email me the detailed report <span class="arr">→</span></button><a class="btn ghost" href="index.html#contact">Talk to an advisor</a><button class="a-link" data-act="restart">Start again</button></div>',
      { progress: 100 });
  }

  function vReport() {
    var l = st.lead;
    return shell('<span class="a-step">Detailed report</span><h2 class="a-q">Where should we send your report?</h2>' +
      '<p class="muted" style="margin-top:6px">A PDF with your full breakdown, the actions for each dimension, and how your answers compare once peer data is available.</p>' +
      '<form class="a-form" data-form="report">' +
      '<label class="a-field"><span>Name</span><input name="name" required autocomplete="name" value="' + esc(l.name || "") + '"></label>' +
      '<label class="a-field"><span>Work email</span><input name="email" type="email" required autocomplete="email" value="' + esc(l.email || "") + '"></label>' +
      '<label class="a-field"><span>Organisation</span><input name="company" required autocomplete="organization" value="' + esc(l.company || "") + '"></label>' +
      '<input name="website" tabindex="-1" autocomplete="off" class="a-hp" aria-hidden="true">' +
      '<fieldset class="a-consent"><legend>Your choices</legend>' +
      '<label><input type="checkbox" name="c_report" checked required> Send me my detailed report <small>(required to send it)</small></label>' +
      '<label><input type="checkbox" name="c_contact"> An advisor may contact me about my result</label>' +
      '<label><input type="checkbox" name="c_bench"> Send me the State of AI Readiness 2026 report in December</label></fieldset>' +
      '<p class="a-privacy" style="margin-top:0">We never share your answers with third parties. Read our <a href="#">privacy notice</a>.</p>' +
      '<div class="a-nav"><button type="button" class="a-link" data-act="result">← Back to my result</button><button class="btn" type="submit">Send my report</button></div></form>');
  }

  function vThanks() {
    var s = score();
    return shell('<div class="a-thanks"><span class="a-tick">✓</span><h2 class="a-q">Your report is on its way.</h2><p class="lede" style="font-size:17px;margin-top:10px">We have sent it to <b>' + esc(st.lead.email) + "</b>. Your level: <b>" + s.level.n + " · " + s.level.name + " (" + s.pct + "%)</b>.</p>" +
      '<div class="a-advisor"><span class="a-av">AI</span><div><b>Finelevate360 AI Advisory</b><span class="muted">Thirty minutes, free and without obligation — we walk through your result and agree one practical next step.</span></div><a class="btn" href="index.html#contact">Book a conversation</a></div>' +
      '<p class="a-proto">Prototype: nothing was sent. In production the WordPress plugin stores the response and Brevo sends the results email.</p>' +
      '<button class="a-link" data-act="restart">Take it again</button></div>');
  }

  function render() {
    var v = { intro: vIntro, profile: vProfile, q: vQuestion, result: vResult, report: vReport, thanks: vThanks }[st.step] || vIntro;
    root.innerHTML = v();
    save();
    var arc = root.querySelector(".a-arc");
    if (arc) setTimeout(function () { arc.style.transition = "stroke-dashoffset 1.2s cubic-bezier(.2,.7,.3,1)"; arc.setAttribute("stroke-dashoffset", arc.getAttribute("data-off")); }, 60);
    var h = root.querySelector("h1,h2"); if (h && st.step !== "intro") { h.setAttribute("tabindex", "-1"); h.focus({ preventScroll: true }); }
    if (st.step !== "intro" && !demo) root.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function answer(v) {
    st.answers[st.i] = v === "ns" ? "ns" : parseInt(v, 10);
    save();
    setTimeout(function () { if (st.i < questions().length - 1) { st.i++; } else { st.step = "result"; } render(); }, 160);
  }

  root.addEventListener("click", function (e) {
    var o = e.target.closest("[data-ans]"); if (o) { answer(o.getAttribute("data-ans")); return; }
    var a = e.target.closest("[data-act]"); if (!a) return;
    var act = a.getAttribute("data-act");
    if (act === "start") { st.step = "profile"; }
    else if (act === "resume") { st.step = st.profile.org ? "q" : "profile"; }
    else if (act === "intro") { st.step = "intro"; }
    else if (act === "back") { if (st.i > 0) st.i--; else st.step = "profile"; }
    else if (act === "report") { st.step = "report"; }
    else if (act === "result") { st.step = "result"; }
    else if (act === "restart") { st = { step: "intro", profile: {}, answers: {}, i: 0, lead: {} }; }
    render();
  });
  root.addEventListener("submit", function (e) {
    e.preventDefault();
    var f = e.target, fd = new FormData(f);
    if (f.dataset.form === "profile") {
      var prevFI = isFI();
      ["org", "size", "country", "role"].forEach(function (k) { st.profile[k] = fd.get(k); });
      if (isFI() !== prevFI) { st.answers = {}; st.i = 0; } // question set changed
      st.step = "q";
    } else if (f.dataset.form === "report") {
      if (fd.get("website")) return; // honeypot
      st.lead = { name: fd.get("name"), email: fd.get("email"), company: fd.get("company"), contact: !!fd.get("c_contact"), bench: !!fd.get("c_bench") };
      st.step = "thanks";
      // Production: POST to /wp-json/finelevate/v1/assessment with profile, answers, lead, consents (see plan §6).
    }
    render();
  });
  document.addEventListener("keydown", function (e) {
    var t = e.target;
    if (st.step !== "q" || e.altKey || e.ctrlKey || e.metaKey) return;
    if (t && t.matches && t.matches("input,select,textarea")) return;
    var k = parseInt(e.key, 10); if (k >= 1 && k <= 5) { answer(k === 5 ? "ns" : SCALE[k - 1].v); }
  });
  render();
})();
