<?php

$localConfig = [];
$localConfigPath = __DIR__ . '/local.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$dbSetting = static function (string $variable, string $key, string $default) use ($localConfig): string {
    $value = getenv($variable);
    return $value !== false ? $value : (string) ($localConfig[$key] ?? $default);
};
$dbHost = $dbSetting('DB_HOST', 'db_host', 'localhost');
$dbUser = $dbSetting('DB_USER', 'db_user', 'root');
$dbPassword = $dbSetting('DB_PASSWORD', 'db_password', '');
$dbName = $dbSetting('DB_NAME', 'db_name', 'shop_db');

// Keep connection failures consistent across PHP versions (including PHP 8.0).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('MySQL connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to connect to MySQL. Check that MySQL is running and the database configuration is correct.');
}
