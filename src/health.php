<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/db.php';

try {
    $connection = db_connection();
    $connection->query('SELECT 1');

    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'database' => 'ok',
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'database' => 'unavailable',
    ], JSON_THROW_ON_ERROR);
}
