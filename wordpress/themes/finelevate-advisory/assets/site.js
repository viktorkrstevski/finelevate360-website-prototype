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
