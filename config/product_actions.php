<?php

// Shared by all product forms. Never trust the price/name/image sent by a browser.
function handle_product_action(mysqli $conn, int $userId, array $input): array
{
    $_SESSION['product_csrf'] ??= bin2hex(random_bytes(32));
    if (!isset($input['add_to_cart']) && !isset($input['add_to_wishlist'])) {
        return [];
    }
    if ($userId <= 0 || !is_string($input['product_csrf'] ?? null)
        || !hash_equals($_SESSION['product_csrf'], $input['product_csrf'])) {
        http_response_code(400);
        return ['Phiên thao tác không hợp lệ. Hãy tải lại trang.'];
    }

    $productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity = filter_var($input['product_quantity'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
    if ($productId === false || $productId === null || $quantity === false) {
        return ['Sản phẩm hoặc số lượng không hợp lệ (từ 1 đến 99 cuốn).'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, name, price, image FROM products WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$book) {
        return ['Không tìm thấy sách. Sản phẩm có thể đã được gỡ khỏi cửa hàng.'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM cart WHERE user_id = ? AND pid = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    mysqli_stmt_execute($stmt);
    $inCart = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($inCart) {
        return ['Sản phẩm đã có trong giỏ hàng.'];
    }

    if (isset($input['add_to_cart'])) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO cart (user_id, pid, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iisdis', $userId, $productId, $book['name'], $book['price'], $quantity, $book['image']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Remove the saved item only after the cart insert succeeds.
        $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE user_id = ? AND pid = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['Sản phẩm đã được thêm vào giỏ hàng.'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM wishlist WHERE user_id = ? AND pid = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    mysqli_stmt_execute($stmt);
    $inWishlist = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($inWishlist) {
        return ['Sản phẩm đã có trong danh sách yêu thích.'];
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO wishlist (user_id, pid, name, price, image) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisds', $userId, $productId, $book['name'], $book['price'], $book['image']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return ['Sản phẩm đã được thêm vào danh sách yêu thích.'];
}
