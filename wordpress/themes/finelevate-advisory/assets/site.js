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
})();
