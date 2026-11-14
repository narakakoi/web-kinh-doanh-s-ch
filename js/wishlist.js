(() => {
  const dialog = document.querySelector('#wishlist-confirm');
  if (!dialog || typeof dialog.showModal !== 'function'
      || typeof HTMLFormElement.prototype.requestSubmit !== 'function') {
    // Keep the existing native confirm() handlers in older browsers.
    return;
  }

  const title = dialog.querySelector('#wishlist-confirm-title');
  const description = dialog.querySelector('#wishlist-confirm-description');
  const deleteButton = dialog.querySelector('#wishlist-confirm-delete');
  const confirmedForms = new WeakSet();
  let pendingForm = null;
  let pendingSubmitter = null;
  let submitting = false;

  document.querySelectorAll('form[data-wishlist-confirm]').forEach((form) => {
    form.removeAttribute('onsubmit');
    form.addEventListener('submit', (event) => {
      if (confirmedForms.has(form)) {
        confirmedForms.delete(form);
        return;
      }

      event.preventDefault();
      if (dialog.open || submitting) return;

      const clearAll = form.dataset.wishlistConfirm === 'clear';
      title.textContent = clearAll ? 'Xóa tất cả sách yêu thích?' : 'Xóa khỏi yêu thích?';
      description.textContent = clearAll
        ? `Bạn muốn bỏ toàn bộ ${form.dataset.bookCount} cuốn sách khỏi danh sách yêu thích? Bạn có thể lưu lại sách từ cửa hàng.`
        : `Bạn muốn bỏ “${form.dataset.bookName}” khỏi danh sách yêu thích? Bạn có thể thêm lại khi cần.`;
      deleteButton.textContent = clearAll ? 'Xóa tất cả' : 'Xóa sách';
      deleteButton.disabled = false;
      pendingForm = form;
      pendingSubmitter = event.submitter;
      document.body.classList.add('bs-wishlist-confirm-open');
      dialog.showModal();
    });
  });

  dialog.querySelectorAll('[data-wishlist-cancel]').forEach((button) => {
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
    pendingForm = null;
    pendingSubmitter = null;
    document.body.classList.remove('bs-wishlist-confirm-open');
  });

  deleteButton.addEventListener('click', () => {
    if (!pendingForm || submitting) return;
    const form = pendingForm;
    const submitter = pendingSubmitter;
    submitting = true;
    deleteButton.disabled = true;
    confirmedForms.add(form);
    dialog.close();
    // Submit the original form, including its existing action, item ID and CSRF.
    if (submitter) form.requestSubmit(submitter);
    else form.requestSubmit();
  });
})();
