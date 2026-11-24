(() => {
  const price = document.getElementById('bookPriceInput');
  const previews = document.querySelectorAll('#formattedPriceHint, #previewBookPrice');
  const formatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 });

  function updatePrice(formatInput) {
    const invalid = price.validity.patternMismatch;
    price.setCustomValidity(invalid ? price.title : '');
    let preview = '0 VNĐ';
    if (invalid) {
      preview = 'Giá chưa hợp lệ';
    } else if (price.value !== '') {
      const digits = price.value.replace(/\.00$/, '').replace(/\./g, '').replace(',', '.');
      const formatted = formatter.format(Number(digits));
      if (formatInput) price.value = formatted;
      preview = formatted + ' VNĐ';
    }
    previews.forEach(element => { element.textContent = preview; });
  }

  price.addEventListener('focus', () => {
    if (!price.validity.valid) return;
    price.value = price.value.replace(/\.00$/, '').replace(/\./g, '');
    price.select();
  });
  price.addEventListener('input', () => updatePrice(false));
  price.addEventListener('blur', () => updatePrice(true));

  document.querySelectorAll('.price-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      price.value = chip.dataset.price;
      updatePrice(true);
    });
  });
})();
