(() => {
  'use strict';
  const contact = document.getElementById('contact-dialog');
  const menu = document.getElementById('mobile-menu');
  const openDialog = (dialog) => {
    document.querySelectorAll('dialog[open]').forEach(open => open.close());
    if (dialog) dialog.showModal();
  };
  document.querySelectorAll('[data-contact-open]').forEach(button => button.addEventListener('click', () => openDialog(contact)));
  document.querySelectorAll('[data-menu-open]').forEach(button => button.addEventListener('click', () => openDialog(menu)));
  document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
  document.querySelectorAll('dialog').forEach(dialog => {
    dialog.addEventListener('click', event => {
      if (event.target === dialog) {
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
      }
    });
  });
  document.querySelectorAll('[data-staging-contact]').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault();
    if (form.reportValidity()) {
      const status = form.querySelector('[data-form-status]');
      status.textContent = status.dataset.message;
    }
  }));
  const viewport = document.querySelector('.partner-banner .logo-viewport');
  if (!viewport) return;
  const banner = viewport.closest('.banner-inner');
  const track = viewport.querySelector('.logo-track');
  const sequence = track.querySelector('.logo-sequence');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const help = document.getElementById('partner-motion-help');
  let copy, frame = 0, cycle = 0, position = 0, applied = viewport.scrollLeft;
  let lastTime = 0, userPaused = false, pointerDown = false, hovered = false;
  const updateStatus = () => {
    if (!help) return;
    const message = help.dataset[reduced.matches ? 'reduced' : userPaused ? 'paused' : 'enabled'];
    if (help.textContent !== message) help.textContent = message;
  };
  const setUserPaused = value => {
    userPaused = value;
    lastTime = 0;
    updateStatus();
  };
  const pauseManual = () => {
    setUserPaused(true);
    position = viewport.scrollLeft;
    lastTime = 0;
  };
  const measure = () => {
    cycle = sequence.getBoundingClientRect().width;
    position = viewport.scrollLeft;
    applied = viewport.scrollLeft;
    lastTime = 0;
  };
  const tick = now => {
    const paused = pointerDown || hovered || banner.contains(document.activeElement) || document.hidden || userPaused;
    if (paused) {
      lastTime = 0;
    } else if (cycle) {
      if (lastTime) {
        position = (position + Math.min(now - lastTime, 64) * 0.024) % cycle;
        viewport.scrollLeft = position;
        applied = viewport.scrollLeft;
      } else {
        position = viewport.scrollLeft % cycle;
      }
      lastTime = now;
    }
    frame = requestAnimationFrame(tick);
  };
  const updateMotion = () => {
    cancelAnimationFrame(frame);
    lastTime = 0;
    updateStatus();
    if (reduced.matches) {
      viewport.removeAttribute('aria-keyshortcuts');
      if (copy) copy.remove();
      copy = null;
      viewport.scrollLeft = applied = position = 0;
      return;
    }
    viewport.setAttribute('aria-keyshortcuts', 'Space');
    if (!copy) {
      copy = sequence.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.inert = true;
      copy.querySelectorAll('img').forEach(img => { img.alt = ''; });
      track.append(copy);
    }
    measure();
    frame = requestAnimationFrame(tick);
  };
  banner.addEventListener('pointerenter', event => { if (event.pointerType !== 'touch') hovered = true; });
  banner.addEventListener('pointerleave', () => { hovered = false; });
  viewport.addEventListener('keydown', event => {
    if (event.key !== ' ' || event.altKey || event.ctrlKey || event.metaKey || reduced.matches) return;
    event.preventDefault();
    if (!event.repeat) setUserPaused(!userPaused);
  });
  viewport.addEventListener('pointerdown', () => { pointerDown = true; pauseManual(); }, { passive: true });
  const releasePointer = () => { if (pointerDown) { pointerDown = false; lastTime = 0; } };
  window.addEventListener('pointerup', releasePointer, { passive: true });
  window.addEventListener('pointercancel', releasePointer, { passive: true });
  viewport.addEventListener('wheel', pauseManual, { passive: true });
  viewport.addEventListener('scroll', () => {
    // Native touch/scrolling pauses persistently; our own frames do not.
    if (Math.abs(viewport.scrollLeft - applied) > 1) pauseManual();
    applied = viewport.scrollLeft;
  }, { passive: true });
  reduced.addEventListener('change', updateMotion);
  new ResizeObserver(measure).observe(sequence);
  updateMotion();
})();

/* Finelevate360 — site interactions v2 (dependency-free; WordPress-ready).
   Motion is purposeful and limited: reveals, 360° scroll ring, count-ups, capability accordion, heat-map fill,
   speaker reel drag, gentle parallax on the flagship band. Everything is disabled under prefers-reduced-motion. */
(function () {
  "use strict";
  var shot = /[?&]shot/.test(location.search); // ?shot = static final state for screenshots and print
  if (shot) document.documentElement.classList.add("shot");
  var reduce = (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) || shot;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

  /* reveal on view */
  // Clip-masked elements report zero visible area to IntersectionObserver, so observe their parent instead.
  var clipParents = [];
  $$(".clip").forEach(function (c) { var p = c.parentElement; if (clipParents.indexOf(p) < 0) { p.setAttribute("data-clip-parent", ""); clipParents.push(p); } });
  var targets = $$(".rv, .line-mask, [data-count], .heat, .bar .f, .step .q, .ai-ring").concat(clipParents);
  function activate(el) {
    if (el.hasAttribute("data-clip-parent")) { $$(":scope > .clip", el).forEach(function (c) { c.classList.add("in"); }); if (!el.classList.contains("rv")) return; }
    if (el.classList.contains("heat")) { el.classList.add("on"); return; }
    if (el.hasAttribute("data-count")) { count(el); return; }
    if (el.matches(".bar .f")) { el.style.width = el.getAttribute("data-w") + "%"; return; }
    if (el.matches(".step .q, .ai-ring")) { var fg = el.querySelector(".fg"); if (fg) fg.style.strokeDashoffset = fg.getAttribute("data-off"); return; }
    el.classList.add("in");
  }
  if (reduce || !("IntersectionObserver" in window)) { targets.forEach(activate); }
  else {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { activate(e.target); io.unobserve(e.target); } }); }, { threshold: .15, rootMargin: "0px 0px -6% 0px" });
    targets.forEach(function (t) { io.observe(t); });
    // Above-the-fold content reveals immediately on load (no dependency on observer timing).
    var vh = window.innerHeight || 800;
    requestAnimationFrame(function () { targets.forEach(function (t) { if (t.getBoundingClientRect().top < vh * .95) activate(t); }); });
    setTimeout(function () { targets.forEach(function (t) { if (t.getBoundingClientRect().top < vh) activate(t); }); }, 120);
    setTimeout(function () { targets.forEach(activate); }, 3500); // safety for throttled/hidden views
  }

  /* count-up figures: <span data-count="24" data-suffix="">0</span> */
  function count(el) {
    if (el.__done) return; el.__done = true;
    var to = parseFloat(el.getAttribute("data-count")), suf = el.getAttribute("data-suffix") || "", pre = el.getAttribute("data-prefix") || "";
    if (reduce) { el.textContent = pre + to + suf; return; }
    var t0 = null, dur = 1600;
    function step(ts) { if (!t0) t0 = ts; var p = Math.min(1, (ts - t0) / dur), v = Math.round(to * (1 - Math.pow(1 - p, 3))); el.textContent = pre + v + suf; if (p < 1) requestAnimationFrame(step); }
    requestAnimationFrame(step); setTimeout(function () { el.textContent = pre + to + suf; }, dur + 300);
  }

  /* 360° scroll ring next to the logo */
  var ring = $(".ring .fg");
  if (ring) {
    var C = 2 * Math.PI * 9; ring.style.strokeDasharray = C; ring.style.strokeDashoffset = C;
    var onScroll = function () { var h = document.documentElement, p = h.scrollTop / Math.max(1, h.scrollHeight - h.clientHeight); ring.style.strokeDashoffset = C * (1 - p); };
    window.addEventListener("scroll", onScroll, { passive: true }); onScroll();
  }

  /* capability accordion */
  $$(".cap-row").forEach(function (row, i) {
    var b = $(".cap-btn", row); if (!b) return;
    b.setAttribute("aria-expanded", row.classList.contains("open") ? "true" : "false");
    b.addEventListener("click", function () {
      var open = !row.classList.contains("open");
      $$(".cap-row.open").forEach(function (r) { r.classList.remove("open"); $(".cap-btn", r).setAttribute("aria-expanded", "false"); });
      if (open) { row.classList.add("open"); b.setAttribute("aria-expanded", "true"); }
    });
  });

  /* speaker reel: drag to scroll */
  $$(".reel").forEach(function (r) {
    var down = false, x0 = 0, s0 = 0, moved = false;
    r.addEventListener("pointerdown", function (e) { if (e.pointerType !== "mouse") return; down = true; moved = false; x0 = e.clientX; s0 = r.scrollLeft; r.style.scrollSnapType = "none"; });
    window.addEventListener("pointermove", function (e) { if (!down) return; var dx = e.clientX - x0; if (Math.abs(dx) > 4) moved = true; r.scrollLeft = s0 - dx; });
    window.addEventListener("pointerup", function () { if (!down) return; down = false; r.style.scrollSnapType = ""; });
    r.addEventListener("click", function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); } }, true);
    var prev = document.querySelector('[data-reel-prev="' + r.id + '"]'), next = document.querySelector('[data-reel-next="' + r.id + '"]');
    if (prev) prev.addEventListener("click", function () { r.scrollBy({ left: -r.clientWidth * .8, behavior: "smooth" }); });
    if (next) next.addEventListener("click", function () { r.scrollBy({ left: r.clientWidth * .8, behavior: "smooth" }); });
  });

  /* gentle parallax on flagship background */
  var par = $$("[data-parallax]");
  if (par.length && !reduce) {
    var tick = false;
    window.addEventListener("scroll", function () {
      if (tick) return; tick = true;
      requestAnimationFrame(function () {
        par.forEach(function (el) { var r = el.parentElement.getBoundingClientRect(), vh = window.innerHeight; if (r.bottom < 0 || r.top > vh) return; var p = (r.top + r.height / 2 - vh / 2) / vh; el.style.transform = "translateY(" + (p * -60).toFixed(1) + "px)"; });
        tick = false;
      });
    }, { passive: true });
  }

  /* heat map cells: deterministic illustrative pattern + staggered fill */
  $$(".heat[data-rows]").forEach(function (h) {
    var rows = h.getAttribute("data-rows").split("|"), cols = 10, html = "<span></span>";
    for (var c = 0; c < cols; c++) html += "<span style='justify-content:center'>" + String.fromCharCode(65 + c) + "</span>";
    rows.forEach(function (name, ri) {
      html += "<span>" + name + "</span>";
      for (var c2 = 0; c2 < cols; c2++) { var v = (ri * 7 + c2 * 11 + ri * c2) % 10, cls = v > 6 ? "l3" : v > 4 ? "l2" : v > 2 ? "l1" : ""; html += "<i class='" + cls + "' style='transition-delay:" + ((ri * cols + c2) * 12) + "ms'></i>"; }
    });
    h.innerHTML = html;
  });

  /* flagship strip close (remembered for the session) */
  var flag = $(".flag"), fx = $(".flag .x");
  try { if (flag && sessionStorage.getItem("fe-flag") === "x") flag.remove(); } catch (e) {}
  if (fx) fx.addEventListener("click", function () { flag.remove(); try { sessionStorage.setItem("fe-flag", "x"); } catch (e) {} });

  /* PM notes */
  var pt = $(".pm-toggle");
  if (pt) { pt.addEventListener("click", function () { var on = document.body.classList.toggle("notes"); pt.textContent = on ? "Hide PM notes" : "Show PM notes"; }); if (location.hash === "#notes") pt.click(); }

  /* demo: open first capability */
  var first = $(".cap-row[data-open]"); if (first) $(".cap-btn", first).click();
})();

/* Prototype v4 extras: contact topics, profiles, chips, prototype forms, speaker preview, photo viewer. */
(function () {
  'use strict';
  var contact = document.getElementById('contact-dialog');
  function openContact(topic) {
    if (!contact) return;
    document.querySelectorAll('dialog[open]').forEach(function (d) { d.close(); });
    if (topic) { var s = contact.querySelector('select'); if (s) s.value = topic; }
    contact.showModal();
  }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href$="#contact-dialog"],[data-contact-topic]');
    if (a) { e.preventDefault(); openContact(a.getAttribute('data-contact-topic')); return; }
    var b = e.target.closest('[data-contact-open][data-topic]');
    if (b && contact) { var s = contact.querySelector('select'); if (s) s.value = b.getAttribute('data-topic'); }
    var p = e.target.closest('[data-bio]');
    if (p) { var d = document.getElementById(p.getAttribute('data-bio')); if (d) { document.querySelectorAll('dialog[open]').forEach(function (x) { x.close(); }); d.showModal(); } }
    var dd = document.querySelector('.lang-dd[open]'); if (dd && !e.target.closest('.lang-dd')) dd.removeAttribute('open');
  });
  document.querySelectorAll('[data-chips]').forEach(function (g) {
    var single = g.hasAttribute('data-single');
    g.querySelectorAll('.chip').forEach(function (c) { c.addEventListener('click', function () {
      if (single) g.querySelectorAll('.chip').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      c.setAttribute('aria-pressed', single ? 'true' : String(c.getAttribute('aria-pressed') !== 'true'));
    }); });
  });
  document.querySelectorAll('form[data-proto]').forEach(function (f) { f.addEventListener('submit', function (e) { e.preventDefault(); if (!f.reportValidity()) return; var ok = f.querySelector('.ok'); if (ok) ok.hidden = false; }); });
  // speaker index: portrait preview on hover / keyboard focus
  var prev = document.querySelector('.spk-prev');
  if (prev) document.querySelectorAll('.spk-row').forEach(function (r) {
    function show() {
      document.querySelectorAll('.spk-row.on').forEach(function (x) { x.classList.remove('on'); }); r.classList.add('on');
      var i = prev.querySelector('img'); i.src = r.getAttribute('data-img'); i.alt = r.getAttribute('data-name');
      prev.querySelector('b').textContent = r.getAttribute('data-name'); prev.querySelector('small').textContent = r.getAttribute('data-role');
    }
    r.addEventListener('mouseenter', show); r.addEventListener('focus', show);
  });
  // photo viewer
  var lb = document.querySelector('.lb');
  if (lb) {
    var items = [].slice.call(document.querySelectorAll('.gal button')), i = 0, last = null, li = lb.querySelector('img'), lc = lb.querySelector('.cap');
    function set(k) { i = (k + items.length) % items.length; li.src = items[i].getAttribute('data-full'); li.alt = items[i].querySelector('img').alt; lc.textContent = items[i].querySelector('.cap').textContent; }
    function open(k) { last = document.activeElement; set(k); lb.classList.add('open'); lb.querySelector('.c').focus(); }
    function close() { lb.classList.remove('open'); if (last) last.focus(); }
    items.forEach(function (b, k) { b.addEventListener('click', function () { open(k); }); });
    lb.querySelector('.c').addEventListener('click', close);
    lb.querySelector('.p').addEventListener('click', function () { set(i - 1); });
    lb.querySelector('.nx').addEventListener('click', function () { set(i + 1); });
    document.addEventListener('keydown', function (e) { if (!lb.classList.contains('open')) return; if (e.key === 'Escape') close(); if (e.key === 'ArrowLeft') set(i - 1); if (e.key === 'ArrowRight') set(i + 1); });
  }
})();
