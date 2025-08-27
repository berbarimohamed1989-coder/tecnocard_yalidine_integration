<?php
// public_html/api/_yalidine_client.php
// يحمّل الإعدادات الآمنة
require_once(dirname(__FILE__, 2) . '/secure/yalidine_config.php');

/**
 * إرسال طلب إلى Yalidine API.
 * @param string $method  GET | POST | PUT | DELETE
 * @param string $endpoint مثل 'wilayas/' أو 'parcels'
 * @param array|null $form بيانات فورم (x-www-form-urlencoded) للطلبات غير GET
 * @return array [$httpStatus, $jsonArray]
 * @throws RuntimeException عند فشل الاتصال
 */
function yalidine_request(string $method, string $endpoint, array $form = null) {
    $url = rtrim(YALIDINE_API_BASE, '/') . '/' . ltrim($endpoint, '/');
    $ch = curl_init();

    $headers = [
        'X-API-ID: '    . YALIDINE_API_ID,
        'X-API-TOKEN: ' . YALIDINE_API_TOKEN,
    ];

    $opts = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 25,
    ];

    if ($form !== null) {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($form);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $opts[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new RuntimeException('Connection error: ' . $err);
    }
    $json = json_decode($resp, true);
    return [$http, $json];
}
