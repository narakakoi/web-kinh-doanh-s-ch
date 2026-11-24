<?php
require_once __DIR__ . '/../config/product_price.php';

foreach ([
    ['0', '0'], ['85000', '85000'], ['85.000', '85000'], ['1000.00', '1000'],
    ['1.250.000', '1250000'], ['1.000,50', '1000.50'], ['9.999.999.999,99', '9999999999.99'],
    ['', null], ['-1000', null], ['12.34', null], ['1e3', null], ['1,234', null],
    ['10.000.000.000', null], ['10000000000', null], ['5abc', null], [[], null],
] as [$input, $expected]) {
    if (parse_product_price($input) !== $expected) {
        throw new RuntimeException('Unexpected parsed price: ' . json_encode($input));
    }
}

foreach ([['1000.00', '1.000'], ['0.00', '0'], ['1000.50', '1.000,50']] as [$stored, $expected]) {
    if (format_product_price($stored) !== $expected) {
        throw new RuntimeException('Unexpected edit value: ' . $stored);
    }
}
echo "Product price checks passed\n";
