<?php

declare(strict_types=1);

function db_connection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = getenv('DB_HOST') ?: 'db';
    $database = getenv('MYSQL_DATABASE') ?: '';
    $user = getenv('MYSQL_USER') ?: '';
    $password = getenv('MYSQL_PASSWORD') ?: '';

    $connection = new mysqli($host, $user, $password, $database);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function ensure_schema(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            message VARCHAR(1000) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}
