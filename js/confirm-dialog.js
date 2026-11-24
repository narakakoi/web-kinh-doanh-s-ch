(() => {
  const dialog = document.querySelector('#bs-confirm-dialog');
  if (!dialog || typeof dialog.showModal !== 'function'
      || typeof HTMLFormElement.prototype.requestSubmit !== 'function') return;

  const title = dialog.querySelector('#bs-confirm-title');
  const description = dialog.querySelector('#bs-confirm-description');
  const acceptButton = dialog.querySelector('#bs-confirm-accept');
  const confirmedForms = new WeakSet();
  let pendingAction = null;

  const openDialog = (source, action) => {
    if (dialog.open) return;

    title.textContent = source.dataset.confirmTitle || 'Xác nhận xóa?';
    description.textContent = source.dataset.confirmDescription || 'Bạn có chắc muốn xóa mục này?';
    acceptButton.textContent = source.dataset.confirmLabel || 'Xóa';
    acceptButton.disabled = false;
    pendingAction = action;
    document.body.classList.add('bs-confirm-dialog-open');
    dialog.showModal();
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const link = target.closest('a[data-confirm-delete]');
    if (!link) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    link.removeAttribute('onclick');
    openDialog(link, () => window.location.assign(link.href));
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('form[data-confirm-delete]')) return;
    if (confirmedForms.has(form)) {
      confirmedForms.delete(form);
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    form.removeAttribute('onsubmit');
    const submitter = event.submitter;
    openDialog(form, () => {
      confirmedForms.add(form);
      if (submitter && submitter.form === form) form.requestSubmit(submitter);
      else form.requestSubmit();
    });
  }, true);

  dialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right
        || event.clientY < bounds.top || event.clientY > bounds.bottom) {
      dialog.close();
    }
  });

  dialog.addEventListener('close', () => {
    pendingAction = null;
    document.body.classList.remove('bs-confirm-dialog-open');
  });

  acceptButton.addEventListener('click', () => {
    if (!pendingAction) return;
    const action = pendingAction;
    pendingAction = null;
    acceptButton.disabled = true;
    dialog.close();
    action();
  });
})();
