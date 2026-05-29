<?php
header('Content-Type: application/json; charset=utf-8');

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function fetchJsonFromUrl(string $url): ?array
{
    $headers = [
        'Accept: application/json',
        'User-Agent: JasaanTourism/1.0 (+http://localhost/jasaan-tourism)'
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $statusCode >= 400) {
            return null;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 12,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function uniqueParts(array $parts): array
{
    $seen = [];
    $unique = [];

    foreach ($parts as $part) {
        $value = trim((string) $part);

        if ($value === '') {
            continue;
        }

        $key = strtolower($value);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $unique[] = $value;
    }

    return $unique;
}

if (isset($_GET['reverse'])) {
    $lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_GET['lng'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($lat === false || $lng === false) {
        sendJson([
            'success' => false,
            'message' => 'Invalid coordinates provided.'
        ], 422);
    }

    $reverseUrl = sprintf(
        'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%s&lon=%s&zoom=18&addressdetails=1',
        rawurlencode((string) $lat),
        rawurlencode((string) $lng)
    );

    $reverseData = fetchJsonFromUrl($reverseUrl);

    if (!$reverseData) {
        sendJson([
            'success' => false,
            'message' => 'Unable to resolve this location right now.'
        ], 502);
    }

    sendJson([
        'success' => true,
        'location' => trim((string) ($reverseData['display_name'] ?? ''))
    ]);
}

$query = trim((string) ($_GET['q'] ?? ''));
$limit = max(1, min(8, (int) ($_GET['limit'] ?? 6)));

if (strlen($query) < 2) {
    sendJson([
        'success' => true,
        'results' => []
    ]);
}

$searchUrl = sprintf(
    'https://photon.komoot.io/api/?q=%s&limit=%d&lat=8.4542&lon=124.6319&lang=en',
    rawurlencode($query),
    $limit
);

$searchData = fetchJsonFromUrl($searchUrl);

if (!$searchData || !isset($searchData['features']) || !is_array($searchData['features'])) {
    sendJson([
        'success' => false,
        'message' => 'Location search is unavailable right now.'
    ], 502);
}

$results = [];

foreach ($searchData['features'] as $feature) {
    $coordinates = $feature['geometry']['coordinates'] ?? [];
    $properties = $feature['properties'] ?? [];

    if (count($coordinates) < 2) {
        continue;
    }

    $longitude = filter_var($coordinates[0], FILTER_VALIDATE_FLOAT);
    $latitude = filter_var($coordinates[1], FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        continue;
    }

    $title = trim((string) (
        $properties['name']
        ?? $properties['street']
        ?? $properties['district']
        ?? $properties['city']
        ?? $properties['county']
        ?? 'Selected location'
    ));

    $labelParts = uniqueParts([
        $properties['name'] ?? '',
        $properties['street'] ?? '',
        $properties['district'] ?? '',
        $properties['suburb'] ?? '',
        $properties['city'] ?? '',
        $properties['county'] ?? '',
        $properties['state'] ?? '',
        $properties['country'] ?? ''
    ]);

    $metaParts = uniqueParts([
        $properties['district'] ?? '',
        $properties['suburb'] ?? '',
        $properties['city'] ?? '',
        $properties['county'] ?? '',
        $properties['state'] ?? '',
        $properties['country'] ?? ''
    ]);

    $label = implode(', ', $labelParts);
    $meta = implode(', ', $metaParts);

    $results[] = [
        'title' => $title,
        'label' => $label !== '' ? $label : $title,
        'meta' => $meta !== '' ? $meta : $title,
        'latitude' => (float) $latitude,
        'longitude' => (float) $longitude,
    ];
}

sendJson([
    'success' => true,
    'results' => $results
]);
