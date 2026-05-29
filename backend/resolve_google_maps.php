<?php
header('Content-Type: application/json; charset=utf-8');

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function normalizeUrl(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $trimmed)) {
        return $trimmed;
    }

    return 'https://' . ltrim($trimmed, '/');
}

function isAllowedShortHost(string $hostname): bool
{
    return (bool) preg_match('/(^|\.)maps\.app\.goo\.gl$/i', $hostname)
        || (bool) preg_match('/^goo\.gl$/i', $hostname);
}

function isGoogleMapsHost(string $hostname): bool
{
    return (bool) preg_match('/(^|\.)((maps\.)?google)\.[a-z.]+$/i', $hostname);
}

$rawUrl = (string) ($_GET['url'] ?? '');
$url = normalizeUrl($rawUrl);

if ($url === '') {
    sendJson([
        'success' => false,
        'message' => 'Missing Google Maps link.'
    ], 422);
}

$parsedUrl = parse_url($url);
$host = strtolower((string) ($parsedUrl['host'] ?? ''));

if ($host === '' || !isAllowedShortHost($host)) {
    sendJson([
        'success' => false,
        'message' => 'Only Google Maps short links are supported here.'
    ], 422);
}

if (!function_exists('curl_init')) {
    sendJson([
        'success' => false,
        'message' => 'Short-link resolution requires cURL support on the server.'
    ], 500);
}

$headers = [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'User-Agent: JasaanTourism/1.0 (+http://localhost/jasaan-tourism)'
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 8,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

curl_close($ch);

if ($response === false || $curlError !== '' || $statusCode >= 400 || $finalUrl === '') {
    sendJson([
        'success' => false,
        'message' => 'Unable to resolve this Google Maps short link right now.'
    ], 502);
}

$resolvedParts = parse_url($finalUrl);
$resolvedHost = strtolower((string) ($resolvedParts['host'] ?? ''));

if ($resolvedHost === '' || !isGoogleMapsHost($resolvedHost)) {
    sendJson([
        'success' => false,
        'message' => 'The resolved link is not a valid Google Maps destination.'
    ], 502);
}

sendJson([
    'success' => true,
    'final_url' => $finalUrl
]);
