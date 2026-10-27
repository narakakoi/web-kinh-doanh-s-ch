<?php

$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASSWORD');
$dbName = getenv('DB_NAME');

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
