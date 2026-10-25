/* The former theme owned this drawer; keep it independent of the redesign. */
document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('.finelevate-filters');
  const trigger = document.querySelector('.finelevate-filter-toggle');
  if (!panel || !trigger) return;
  panel.id ||= 'finelevate-mobile-filters';
  trigger.setAttribute('aria-controls', panel.id);
  trigger.setAttribute('aria-expanded', 'false');
  const overlay = document.createElement('div');
  overlay.className = 'finelevate-filter-overlay';
  overlay.setAttribute('aria-hidden', 'true');
  document.body.append(overlay);
  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'finelevate-filter-close';
  close.textContent = document.documentElement.lang === 'mk' ? '✕ Затвори' : '✕ Close';
  panel.prepend(close);
  const toggle = open => {
    panel.classList.toggle('active', open);
    overlay.classList.toggle('active', open);
    document.body.classList.toggle('finelevate-lock', open);
    trigger.setAttribute('aria-expanded', String(open));
    (open ? close : trigger).focus();
  };
  trigger.addEventListener('click', () => toggle(true));
  close.addEventListener('click', () => toggle(false));
  overlay.addEventListener('click', () => toggle(false));
  document.addEventListener('keydown', event => {
    if (!panel.classList.contains('active')) return;
    if (event.key === 'Escape') { event.preventDefault(); toggle(false); }
    if (event.key !== 'Tab') return;
    const controls = [...panel.querySelectorAll('button, input, a[href]')].filter(e => !e.disabled && e.getClientRects().length);
    const first = controls[0], last = controls.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
  });
});
