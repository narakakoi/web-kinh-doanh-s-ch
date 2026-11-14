(() => {
  const toast = document.querySelector('#product-toast');
  if (!toast) return;
  const toastMessage = toast.querySelector('[data-toast-message]');
  let dismissTimer;
  let dismissAutomatically = false;

  function positionToast() {
    const header = document.querySelector('.header');
    toast.style.top = `${header ? Math.max(0, header.getBoundingClientRect().bottom) + 12 : 16}px`;
  }
  function scheduleDismiss() {
    window.clearTimeout(dismissTimer);
    if (dismissAutomatically && !toast.matches(':hover, :focus-within')) {
      dismissTimer = window.setTimeout(() => { toast.hidden = true; }, 4500);
    }
  }
  function showNotice(message, error = false) {
    toast.dataset.kind = error ? 'error' : 'success';
    toastMessage.textContent = message;
    toast.hidden = false;
    dismissAutomatically = !error;
    positionToast();
    scheduleDismiss();
  }
  toast.querySelector('[data-toast-close]').addEventListener('click', () => {
    toast.hidden = true;
    window.clearTimeout(dismissTimer);
  });
  toast.addEventListener('mouseenter', () => window.clearTimeout(dismissTimer));
  toast.addEventListener('mouseleave', scheduleDismiss);
  toast.addEventListener('focusin', () => window.clearTimeout(dismissTimer));
  toast.addEventListener('focusout', scheduleDismiss);
  window.addEventListener('resize', positionToast);
  if (!toast.hidden) showNotice(toastMessage.textContent, toast.dataset.kind === 'error');

  if (typeof window.fetch !== 'function' || typeof FormData !== 'function'
      || typeof AbortController !== 'function' || typeof SubmitEvent !== 'function'
      || !('submitter' in SubmitEvent.prototype)) return;
  const forms = [...document.querySelectorAll('form[data-product-form]')];
  const busyProducts = new Set();
  const productId = (form) => form.querySelector('[name="product_id"]').value;

  function updateState(result) {
    document.querySelectorAll('[data-cart-count]').forEach((span) => {
      span.textContent = `(${result.cart_count})`;
    });
    document.querySelectorAll('[data-wishlist-count]').forEach((span) => {
      span.textContent = `(${result.wishlist_count})`;
    });
    const saved = new Set(result.wishlist_ids.map(String));
    forms.forEach((form) => {
      const button = form.querySelector('[name="add_to_wishlist"]');
      const isSaved = saved.has(productId(form));
      button.dataset.wishlistSaved = String(isSaved);
      button.title = isSaved ? 'Đã lưu vào yêu thích' : 'Thêm vào yêu thích';
      const icon = button.querySelector('i');
      icon.classList.toggle('fas', isSaved);
      icon.classList.toggle('far', !isSaved);
    });
  }

  forms.forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const id = productId(form);
      if (busyProducts.has(id)) return;
      const button = event.submitter;
      if (!button || !['add_to_cart', 'add_to_wishlist'].includes(button.name)) {
        showNotice('Vui lòng chọn Yêu thích hoặc Thêm vào giỏ hàng.', true);
        return;
      }

      // FormData(form) omits submit buttons: send only the button actually used.
      const data = new FormData(form);
      data.delete('add_to_cart');
      data.delete('add_to_wishlist');
      data.set(button.name, button.value || '1');
      const controls = forms.filter((entry) => productId(entry) === id)
        .flatMap((entry) => [...entry.querySelectorAll('[type="submit"]')]);
      const disabled = controls.map((control) => control.disabled);
      busyProducts.add(id);
      controls.forEach((control) => { control.disabled = true; });
      const controller = new AbortController();
      const timeout = window.setTimeout(() => controller.abort(), 12000);
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal,
        });
        if (response.redirected && new URL(response.url).pathname.endsWith('/login.php')) {
          window.location.assign(response.url);
          return;
        }
        if (!response.headers.get('Content-Type')?.includes('application/json')) {
          throw new Error('Invalid server response');
        }
        const result = await response.json();
        if (response.ok && result.success) updateState(result);
        showNotice(result.message || 'Không thể xử lý thao tác lúc này.', !response.ok || !result.success);
      } catch (error) {
        showNotice('Chưa nhận được phản hồi. Vui lòng kiểm tra kết nối và thử lại.', true);
      } finally {
        window.clearTimeout(timeout);
        busyProducts.delete(id);
        controls.forEach((control, index) => { control.disabled = disabled[index]; });
      }
    });
  });
})();
