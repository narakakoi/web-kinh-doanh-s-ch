<?php

/**
 * orders.total_products chỉ lưu dạng chuỗi "Tên sách (2), Tên sách khác (1)".
 * Tách chuỗi đó thành từng cuốn để trang đơn hàng tính được đơn giá / thành tiền.
 *
 * @param string $total_products
 * @return array<int, array{name: string, qty: int}>
 */
function parse_order_items(string $total_products): array
{
    $items = [];

    if (preg_match_all('/(.+?)\s*\((\d+)\)/u', $total_products, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $name = trim($match[1], " ,");
            $quantity = (int) $match[2];

            if ($name === '') {
                continue;
            }

            // Cùng một tựa sách có thể xuất hiện nhiều lần: cộng dồn số lượng.
            $existing = null;
            foreach ($items as $index => $item) {
                if ($item['name'] === $name) {
                    $existing = $index;
                    break;
                }
            }

            if ($existing !== null) {
                $items[$existing]['qty'] += $quantity;
            } else {
                $items[] = ['name' => $name, 'qty' => $quantity];
            }
        }
    }

    return $items;
}
