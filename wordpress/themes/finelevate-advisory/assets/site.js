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
  let copy, frame = 0, cycle = 0, position = 0, applied = viewport.scrollLeft;
  let lastTime = 0, resumeAt = 0, pointerDown = false, hovered = false;
  const pauseManual = () => {
    resumeAt = performance.now() + 6000;
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
    const paused = pointerDown || hovered || banner.contains(document.activeElement) || document.hidden || now < resumeAt;
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
    if (reduced.matches) {
      if (copy) copy.remove();
      copy = null;
      viewport.scrollLeft = applied = position = 0;
      return;
    }
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
  viewport.addEventListener('pointerdown', () => { pointerDown = true; pauseManual(); }, { passive: true });
  const releasePointer = () => { if (pointerDown) { pointerDown = false; pauseManual(); } };
  window.addEventListener('pointerup', releasePointer, { passive: true });
  window.addEventListener('pointercancel', releasePointer, { passive: true });
  viewport.addEventListener('wheel', pauseManual, { passive: true });
  viewport.addEventListener('scroll', () => {
    // Native scrolling and momentum extend the interruption; our own frames do not.
    if (Math.abs(viewport.scrollLeft - applied) > 1) pauseManual();
    applied = viewport.scrollLeft;
  }, { passive: true });
  reduced.addEventListener('change', updateMotion);
  new ResizeObserver(measure).observe(sequence);
  updateMotion();
})();
