<?php

/**
 * Stops one browser/IP from sending too many requests in a short time.
 * The data is saved in a small JSON file inside the system temp folder.
 */
function jt_apply_rate_limit(int $maxRequests = 120, int $windowSeconds = 60): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    $now = time();
    $key = hash('sha256', $ip . '|' . $path);
    $storagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jasaan_tourism_rate_limit.json';

    $records = [];
    if (is_file($storagePath)) {
        $decoded = json_decode((string) file_get_contents($storagePath), true);
        $records = is_array($decoded) ? $decoded : [];
    }

    // Remove old records so the file stays small.
    foreach ($records as $recordKey => $record) {
        if (($record['reset_at'] ?? 0) <= $now) {
            unset($records[$recordKey]);
        }
    }

    $record = $records[$key] ?? [
        'count' => 0,
        'reset_at' => $now + $windowSeconds,
    ];

    $record['count']++;
    $records[$key] = $record;
    file_put_contents($storagePath, json_encode($records), LOCK_EX);

    if ($record['count'] <= $maxRequests) {
        return;
    }

    $retryAfter = max(1, (int) $record['reset_at'] - $now);
    http_response_code(429);
    header('Retry-After: ' . $retryAfter);

    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please wait a moment and try again.',
        ]);
    } else {
        echo 'Too many requests. Please wait a moment and try again.';
    }

    exit;
}

jt_apply_rate_limit();
