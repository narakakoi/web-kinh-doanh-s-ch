<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $statusCode, array $response): void
{
    http_response_code($statusCode);
    echo json_encode($response);
    exit();
}

function amountToMinorUnits($amount): ?int
{
    $amount = (string) $amount;
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
        return null;
    }

    $parts = explode('.', $amount, 2);
    $fraction = str_pad($parts[1] ?? '', 2, '0');

    return ((int) $parts[0] * 100) + (int) $fraction;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed']);
}

$webhookKey = $localConfig['sepay_webhook_api_key'] ?? getenv('SEPAY_WEBHOOK_API_KEY');
if ($webhookKey === false || trim($webhookKey) === '') {
    error_log('SePay webhook rejected: SEPAY_WEBHOOK_API_KEY is not configured.');
    respond(503, ['success' => false, 'message' => 'Webhook is not configured']);
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
foreach ($headers as $headerName => $headerValue) {
    if (strtolower($headerName) === 'authorization') {
        $authorization = $headerValue;
        break;
    }
}
if (!hash_equals('Apikey ' . trim($webhookKey), trim($authorization))) {
    respond(401, ['success' => false, 'message' => 'Unauthorized']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    respond(400, ['success' => false, 'message' => 'Invalid JSON payload']);
}

$transferType = strtolower((string) ($payload['transferType'] ?? ''));
$transferAmount = $payload['transferAmount'] ?? null;
$content = trim((string) ($payload['content'] ?? ''));
$referenceCode = trim((string) ($payload['referenceCode'] ?? ''));

if ($transferType !== 'in' || $referenceCode === '' || strlen($referenceCode) > 100 || !is_numeric($transferAmount)) {
    respond(400, ['success' => false, 'message' => 'Missing or invalid transaction fields']);
}

$receivedAmount = amountToMinorUnits($transferAmount);
if ($receivedAmount === null || $receivedAmount <= 0) {
    respond(400, ['success' => false, 'message' => 'Invalid transfer amount']);
}

if (!preg_match('/(?:^|[^A-Z0-9])DH(\d+)(?:$|[^0-9])/i', $content, $matches)) {
    respond(200, ['success' => true, 'message' => 'No matching order reference']);
}
$orderId = (int) $matches[1];

try {
    mysqli_begin_transaction($conn);

    $orderStmt = mysqli_prepare(
        $conn,
        'SELECT total_price, method, payment_status, payment_reference FROM `orders` WHERE id = ? FOR UPDATE'
    );
    mysqli_stmt_bind_param($orderStmt, 'i', $orderId);
    mysqli_stmt_execute($orderStmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($orderStmt));
    mysqli_stmt_close($orderStmt);

    if (!$order || $order['method'] !== 'sepay_qr') {
        mysqli_rollback($conn);
        respond(200, ['success' => true, 'message' => 'No matching payment order']);
    }

    if ($order['payment_status'] === 'completed') {
        mysqli_commit($conn);
        if ($order['payment_reference'] === $referenceCode) {
            respond(200, ['success' => true, 'message' => 'Transaction already processed']);
        }
        respond(409, ['success' => false, 'message' => 'Order already paid']);
    }

    $referenceStmt = mysqli_prepare(
        $conn,
        'SELECT id FROM `orders` WHERE payment_reference = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($referenceStmt, 's', $referenceCode);
    mysqli_stmt_execute($referenceStmt);
    $existingReference = mysqli_fetch_assoc(mysqli_stmt_get_result($referenceStmt));
    mysqli_stmt_close($referenceStmt);
    if ($existingReference) {
        mysqli_rollback($conn);
        respond(200, ['success' => true, 'message' => 'Transaction already processed']);
    }

    $expectedAmount = amountToMinorUnits($order['total_price']);
    if ($order['payment_status'] !== 'pending' || $expectedAmount !== $receivedAmount) {
        mysqli_rollback($conn);
        respond(200, ['success' => true, 'message' => 'Payment does not match a pending order']);
    }

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE `orders` SET payment_status = 'completed', payment_reference = ?, paid_at = NOW() WHERE id = ? AND payment_status = 'pending'"
    );
    mysqli_stmt_bind_param($updateStmt, 'si', $referenceCode, $orderId);
    mysqli_stmt_execute($updateStmt);
    $updatedRows = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($updatedRows !== 1) {
        mysqli_rollback($conn);
        respond(409, ['success' => false, 'message' => 'Order status changed during processing']);
    }

    mysqli_commit($conn);
    respond(200, ['success' => true]);
} catch (mysqli_sql_exception $exception) {
    mysqli_rollback($conn);
    if ((int) $exception->getCode() === 1062) {
        respond(200, ['success' => true, 'message' => 'Transaction already processed']);
    }
    error_log('SePay webhook database error: ' . $exception->getMessage());
    respond(500, ['success' => false, 'message' => 'Unable to process payment notification']);
}
