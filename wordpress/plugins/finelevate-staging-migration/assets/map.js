/* Independent mobile drawer; preserve native radio/checkbox semantics. */
document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('.finelevate-filters');
  const trigger = document.querySelector('.finelevate-filter-toggle');
  if (!panel || !trigger) return;
  const mobile = matchMedia('(max-width:768px)');
  panel.id ||= 'finelevate-mobile-filters';
  trigger.type = 'button';
  trigger.setAttribute('aria-controls', panel.id);
  trigger.setAttribute('aria-expanded', 'false');
  panel.setAttribute('aria-label', trigger.textContent.replace('☰', '').trim());
  panel.querySelectorAll('.check-indicator').forEach(e => e.setAttribute('aria-hidden', 'true'));
  const overlay = document.createElement('div');
  overlay.className = 'finelevate-filter-overlay';
  overlay.setAttribute('aria-hidden', 'true');
  document.body.append(overlay);
  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'finelevate-filter-close';
  close.textContent = '✕ ' + ({mk:'Затвори',sr:'Zatvori',hr:'Zatvori',sl:'Zapri',me:'Zatvori'}[document.documentElement.lang.split('-')[0]] || 'Close');
  panel.prepend(close);
  let open = false, scrollY = 0;
  const lock = enabled => {
    if (enabled) {
      scrollY = window.scrollY;
      document.body.style.position = 'fixed';
      document.body.style.top = `-${scrollY}px`;
      document.body.style.width = '100%';
    } else {
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.width = '';
      window.scrollTo({top: scrollY, behavior: 'instant'});
    }
    document.body.classList.toggle('finelevate-lock', enabled);
  };
  const sync = () => {
    panel.inert = mobile.matches && !open;
    panel.setAttribute('aria-hidden', String(mobile.matches && !open));
    panel.setAttribute('role', mobile.matches ? 'dialog' : 'region');
    if (mobile.matches && open) panel.setAttribute('aria-modal', 'true');
    else panel.removeAttribute('aria-modal');
  };
  const toggle = value => {
    if (!mobile.matches) return;
    open = value;
    if (open) { lock(true); panel.inert = false; }
    panel.classList.toggle('active', open);
    overlay.classList.toggle('active', open);
    trigger.setAttribute('aria-expanded', String(open));
    if (open) close.focus({preventScroll:true});
    else { lock(false); trigger.focus({preventScroll:true}); }
    sync();
  };
  trigger.addEventListener('click', () => toggle(true));
  close.addEventListener('click', () => toggle(false));
  overlay.addEventListener('click', () => toggle(false));
  mobile.addEventListener('change', () => {
    if (open) { open = false; lock(false); }
    panel.classList.remove('active'); overlay.classList.remove('active');
    trigger.setAttribute('aria-expanded', 'false'); sync();
  });
  document.addEventListener('keydown', event => {
    if (!open || !panel.contains(document.activeElement)) return;
    if (event.key === 'Escape') { event.preventDefault(); toggle(false); }
    if (event.key !== 'Tab') return;
    const controls = [...panel.querySelectorAll('button, input, a[href], select')].filter(e => !e.disabled && e.getClientRects().length);
    const first = controls[0], last = controls.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
  });
  sync();
});
