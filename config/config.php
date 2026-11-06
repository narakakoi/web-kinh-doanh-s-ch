<?php

$localConfig = [];
$localConfigPath = __DIR__ . '/local.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$dbHost = $localConfig['db_host'] ?? getenv('DB_HOST');
$dbUser = $localConfig['db_user'] ?? getenv('DB_USER');
$dbPassword = $localConfig['db_password'] ?? getenv('DB_PASSWORD');
$dbName = $localConfig['db_name'] ?? getenv('DB_NAME');

$dbHost = $dbHost === false ? 'localhost' : $dbHost;
$dbUser = $dbUser === false ? 'root' : $dbUser;
$dbPassword = $dbPassword === false ? '' : $dbPassword;
$dbName = $dbName === false ? 'shop_db' : $dbName;

try {
    $conn = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('MySQL connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to connect to MySQL. Check that MySQL is running and the database configuration is correct.');
}
