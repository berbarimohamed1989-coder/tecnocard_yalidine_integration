<?php
// public_html/api/create_parcel.php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once(__DIR__ . '/_yalidine_client.php');

try {
    // Only POST (JSON body)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // CORS preflight (إن احتجت ذلك)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        http_response_code(204);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
    }

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit;
    }

    // Extract fields from frontend
    $customer_name  = trim($data['name']    ?? '');
    $customer_phone = trim($data['phone']   ?? '');
    $wilaya_code    = trim($data['wilaya']  ?? '');
    $commune_name   = trim($data['commune'] ?? '');
    $variant_name   = trim($data['variant'] ?? 'Carte NFC');
    $quantity       = max(1, (int)($data['quantity'] ?? 1));
    $note           = trim($data['note'] ?? '');
    $price_total    = (int)($data['price_total'] ?? 0);      // COD
    $declared_value = (int)($data['declared_value'] ?? $price_total);

    if ($customer_name === '' || $customer_phone === '' || $wilaya_code === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Missing required fields']); exit;
    }

    // Yalidine form
    $form = [
        'to_name'        => $customer_name,
        'to_phone'       => $customer_phone,
        'to_wilaya'      => $wilaya_code,
        'to_address'     => $commune_name !== '' ? $commune_name : '—',
        'product_type'   => $variant_name,
        'items'          => $quantity,
        'price'          => $price_total,    // COD يُحتسب على الأكبر بين price و declared_value
        'declared_value' => $declared_value,
        'fragile'        => 0,
        'note'           => $note,
    ];

    list($code, $json) = yalidine_request('POST', 'parcels', $form);

    if ($code >= 200 && $code < 300) {
        $tracking = $json['tracking'] ?? ($json['data']['tracking'] ?? null);

        // Save to CSV on server (optional but useful)
        $csvPath = __DIR__ . '/orders_server.csv';
        $isNew   = !file_exists($csvPath);
        $f = fopen($csvPath, 'a');
        if ($isNew) {
            fputcsv($f, ['timestamp','tracking','name','phone','wilaya','commune','variant','qty','price_total','declared_value','note']);
        }
        fputcsv($f, [date('c'), $tracking, $customer_name, $customer_phone, $wilaya_code, $commune_name, $variant_name, $quantity, $price_total, $declared_value, $note]);
        fclose($f);

        echo json_encode(['ok'=>true,'tracking'=>$tracking,'api'=>$json], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code($code ?: 500);
        echo json_encode(['ok'=>false,'error'=>'API error','details'=>$json], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server exception','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
