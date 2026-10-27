<?php
require_once __DIR__ . '/../config/config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? null;
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if ($userId === null || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT payment_status FROM `orders` WHERE id = ? AND user_id = ? AND method = ?'
);
$method = 'sepay_qr';
mysqli_stmt_bind_param($stmt, 'iis', $orderId, $userId, $method);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

echo json_encode(['payment_status' => $order['payment_status']]);
