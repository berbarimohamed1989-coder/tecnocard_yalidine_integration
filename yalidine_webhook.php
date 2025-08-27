<?php
// public_html/api/yalidine_webhook.php
// استقبل إشعارات Webhook من Yalidine
header('Content-Type: application/json; charset=utf-8');

// أمان بسيط عبر سرّ في رابط الـWebhook: ?secret=CHANGE_ME
$expectedSecret = 'CHANGE_ME'; // غيّرها ثم استعمل نفس القيمة عند تسجيل عنوان الـWebhook في Yalidine
if (!isset($_GET['secret']) || $_GET['secret'] !== $expectedSecret) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit;
}

// احفظ الحمولة لغايات التحقق والتجارب
file_put_contents(__DIR__ . '/webhook_payloads.jsonl', $raw . PHP_EOL, FILE_APPEND);

// TODO: حدّث قاعدة بياناتك أو CSV بناءً على نوع الحدث والحالة
// مثال:
/*
$type   = $payload['type']   ?? '';
$status = $payload['status'] ?? '';
$tracking = $payload['tracking'] ?? '';
*/

http_response_code(200);
echo json_encode(['ok'=>true]);
