<?php
$productNotice = implode(' ', $message ?? []);
$productNoticeError = (http_response_code() ?: 200) >= 400;
?>
<div id="product-toast" class="bs-product-toast" role="status" aria-live="polite" aria-atomic="true" data-kind="<?= $productNoticeError ? 'error' : 'success' ?>" <?= $productNotice === '' ? 'hidden' : '' ?>>
    <svg class="bs-toast-symbol" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path class="bs-toast-check" d="m8 12 3 3 5-6"/>
        <path class="bs-toast-error" d="M12 7v6m0 4h.01"/>
    </svg>
    <span data-toast-message><?= htmlspecialchars($productNotice, ENT_QUOTES, 'UTF-8') ?></span>
    <button type="button" data-toast-close aria-label="Đóng thông báo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
</div>
