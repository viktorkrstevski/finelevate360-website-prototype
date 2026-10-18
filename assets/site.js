/* Finelevate360 — site interactions v2 (dependency-free; WordPress-ready).
   Motion is purposeful and limited: reveals, 360° scroll ring, count-ups, capability accordion, heat-map fill,
   speaker reel drag, gentle parallax on the flagship band. Everything is disabled under prefers-reduced-motion. */
(function () {
  "use strict";
  document.documentElement.classList.add("js");
  var shot = /[?&]shot/.test(location.search); // ?shot = static final state for screenshots and print
  if (shot) document.documentElement.classList.add("shot");
  var reduce = (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) || shot;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

  /* Mobile disclosure navigation: native links remain usable without JavaScript. */
  var burger = $(".burger"), mobile = $("#mobile-nav");
  function closeMenu(focus) {
    if (!mobile || !burger) return;
    mobile.hidden = true; burger.setAttribute("aria-expanded", "false");
    if (focus) burger.focus();
  }
  if (burger && mobile) {
    burger.addEventListener("click", function () {
      var open = burger.getAttribute("aria-expanded") !== "true";
      burger.setAttribute("aria-expanded", String(open)); mobile.hidden = !open;
    });
    mobile.addEventListener("click", function (e) { if (e.target.closest("a")) closeMenu(false); });
    document.addEventListener("keydown", function (e) { if (e.key === "Escape" && !mobile.hidden) closeMenu(true); });
    document.addEventListener("click", function (e) { if (!e.target.closest(".hdr") && !mobile.hidden) closeMenu(false); });
    window.addEventListener("resize", function () { if (window.innerWidth > 1100) closeMenu(false); });
  }
  $$(".nav > li > button").forEach(function (button) {
    var item = button.parentElement, panel = $(".mega", item); if (!panel) return;
    item.addEventListener("pointerenter", function () { item.classList.remove("menu-dismissed"); });
    button.addEventListener("focus", function () { item.classList.remove("menu-dismissed"); });
    button.setAttribute("aria-expanded", "false");
    button.addEventListener("click", function () {
      var open = !item.classList.contains("menu-open");
      item.classList.remove("menu-dismissed");
      $$(".nav > li.menu-open").forEach(function (li) { li.classList.remove("menu-open"); $("button", li).setAttribute("aria-expanded", "false"); });
      item.classList.toggle("menu-open", open); button.setAttribute("aria-expanded", String(open));
    });
    item.addEventListener("keydown", function (e) { if (e.key === "Escape") { button.focus(); item.classList.remove("menu-open"); item.classList.add("menu-dismissed"); button.setAttribute("aria-expanded", "false"); } });
    item.addEventListener("focusout", function (e) { if (!item.contains(e.relatedTarget)) { item.classList.remove("menu-open"); button.setAttribute("aria-expanded", "false"); } });
  });

  /* Form preference controls share the same behaviour on every page. */
  $$("[data-chips]").forEach(function (group) {
    $$(".chip", group).forEach(function (chip) { chip.addEventListener("click", function () {
      var selected = chip.getAttribute("aria-pressed") === "true";
      if (group.hasAttribute("data-single")) $$(".chip", group).forEach(function (c) { c.setAttribute("aria-pressed", "false"); });
      chip.setAttribute("aria-pressed", String(group.hasAttribute("data-single") || !selected));
    }); });
  });

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
  if (pt && !/[?&]review(?:[=&]|$)/.test(location.search) && location.hash !== "#notes") pt.hidden = true;
  if (pt) { pt.addEventListener("click", function () { var on = document.body.classList.toggle("notes"); pt.textContent = on ? "Hide PM notes" : "Show PM notes"; }); if (location.hash === "#notes") pt.click(); }

  /* demo: open first capability */
  var first = $(".cap-row[data-open]"); if (first) $(".cap-btn", first).click();
})();
