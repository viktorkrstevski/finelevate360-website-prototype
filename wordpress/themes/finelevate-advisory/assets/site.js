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
  const banner = document.querySelector('[data-banner]');
  if (!banner) return;
  const track = banner.querySelector('[data-logo-track]');
  const toggle = banner.querySelector('[data-motion-toggle]');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let paused = false;
  const update = () => {
    banner.classList.toggle('is-paused', paused);
    toggle.setAttribute('aria-pressed', String(paused));
    toggle.textContent = paused ? toggle.dataset.play : toggle.dataset.pause;
    toggle.hidden = reduced.matches;
  };
  toggle.addEventListener('click', () => { paused = !paused; update(); });
  reduced.addEventListener('change', update);
  const measure = () => {
    const width = track.querySelector('.logo-sequence').getBoundingClientRect().width;
    track.style.setProperty('--track-duration', `${Math.max(width / 24, 1)}s`);
  };
  new ResizeObserver(measure).observe(track.querySelector('.logo-sequence'));
  measure();
  update();
})();
