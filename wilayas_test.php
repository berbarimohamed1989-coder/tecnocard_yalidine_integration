<?php
// public_html/api/wilayas_test.php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/_yalidine_client.php');
try {
    list($code, $json) = yalidine_request('GET', 'wilayas/');
    http_response_code($code ?: 200);
    echo json_encode($json, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
