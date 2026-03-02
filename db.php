<?php

declare(strict_types=1);

function envValue(string $key, ?string $default = null): ?string
{
    static $loaded = false;
    if (!$loaded) {
        loadDotEnv();
        $loaded = true;
    }

    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function loadDotEnv(): void
{
    $paths = [
        __DIR__ . DIRECTORY_SEPARATOR . '.env',
        __DIR__ . DIRECTORY_SEPARATOR . '.env.local',
    ];

    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || substr($line, 0, 1) === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, "\"'");
            if ($name !== '' && getenv($name) === false) {
                putenv($name . '=' . $value);
            }
        }
    }
}

function supabaseUrl(): string
{
    $url = envValue('SUPABASE_URL');
    if (!is_string($url) || $url === '') {
        throw new RuntimeException('SUPABASE_URL is missing. Configure it in environment or .env file.');
    }
    return rtrim($url, '/');
}

function supabaseServiceKey(): string
{
    $key = envValue('SUPABASE_SERVICE_ROLE_KEY');
    if (!is_string($key) || $key === '') {
        throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is missing. Configure it in environment or .env file.');
    }
    return $key;
}

function supabaseRequest(string $method, string $path, array $query = [], ?array $body = null): array
{
    $url = supabaseUrl() . '/rest/v1/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $headers = [
        'apikey: ' . supabaseServiceKey(),
        'Authorization: Bearer ' . supabaseServiceKey(),
        'Content-Type: application/json',
    ];

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize HTTP client.');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if (!is_string($raw)) {
        throw new RuntimeException('No response from Supabase.' . ($curlErr !== '' ? ' ' . $curlErr : ''));
    }

    $decoded = json_decode($raw, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = is_array($decoded) && isset($decoded['message']) ? (string)$decoded['message'] : $raw;
        throw new RuntimeException('Supabase request failed: ' . $msg);
    }

    return is_array($decoded) ? $decoded : [];
}

function nowIso(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
}

function ensureDefaultUserAccounts(): void
{
    upsertDefaultUser('admin', 'admin123', 'admin');
    upsertDefaultUser('ayusabrina@pkl.com', 'user123', 'user');
}

function upsertDefaultUser(string $username, string $plainPassword, string $role): void
{
    $existing = supabaseRequest('GET', 'users', [
        'select' => 'id,username',
        'username' => 'eq.' . $username,
        'limit' => '1'
    ]);
    if ($existing !== []) {
        return;
    }

    supabaseRequest('POST', 'users', [], [[
        'username' => $username,
        'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => nowIso()
    ]]);
}

