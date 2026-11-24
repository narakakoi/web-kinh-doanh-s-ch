<?php

// DECIMAL(12,2) allows ten whole digits. Also accept the old form's 1000.00.
const PRODUCT_PRICE_WHOLE = '(?:[0-9]{1,10}|[0-9]{1,3}(?:\.[0-9]{3}){1,2}|[0-9](?:\.[0-9]{3}){3})';
const PRODUCT_PRICE_PATTERN = '(?:' . PRODUCT_PRICE_WHOLE . '(?:,[0-9]{1,2})?|[0-9]{1,10}\.00)';
const PRODUCT_PRICE_HINT = 'Nhập giá từ 0 đến 9.999.999.999,99 VNĐ. Ví dụ: 85000 hoặc 85.000.';

function parse_product_price($input): ?string
{
    if (!is_string($input) || !preg_match('/\A' . PRODUCT_PRICE_PATTERN . '\z/', $input)) {
        return null;
    }
    if (str_ends_with($input, '.00')) {
        return substr($input, 0, -3);
    }
    return str_replace(['.', ','], ['', '.'], $input);
}

function format_product_price(string $storedPrice): string
{
    $formatted = number_format((float) $storedPrice, 2, ',', '.');
    return str_ends_with($formatted, ',00') ? substr($formatted, 0, -3) : $formatted;
}
