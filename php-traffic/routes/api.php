<?php

use App\Controllers\TrafficController;
use App\Controllers\IncidentController;
use App\Controllers\HealthController;

/**
 * Router sederhana berbasis method + path.
 *
 * @param string   $method   HTTP method (GET, POST, PATCH, dll)
 * @param string   $pattern  Path pattern, mendukung :param
 * @param callable $handler  Fungsi yang dieksekusi jika cocok
 */
function route(string $method, string $pattern, callable $handler): bool
{
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $requestPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Ubah :param menjadi regex capture group
    $regex = preg_replace('/:[a-zA-Z_]+/', '([^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if ($requestMethod !== $method) return false;
    if (!preg_match($regex, $requestPath, $matches)) return false;

    array_shift($matches); // buang full match
    $handler(...$matches);
    return true;
}

// ── Health ────────────────────────────────────────────────────────────────
$matched = route('GET', '/health', function () {
    (new HealthController())->index();
});

// ── Traffic Readings ──────────────────────────────────────────────────────
$matched = $matched ?: route('POST', '/api/traffic/readings', function () {
    (new TrafficController())->store();
});

$matched = $matched ?: route('GET', '/api/traffic/current', function () {
    (new TrafficController())->current();
});

$matched = $matched ?: route('GET', '/api/traffic/history', function () {
    (new TrafficController())->history();
});

// ── Incidents ─────────────────────────────────────────────────────────────
$matched = $matched ?: route('POST', '/api/traffic/incidents', function () {
    (new IncidentController())->store();
});

$matched = $matched ?: route('GET', '/api/traffic/incidents', function () {
    (new IncidentController())->active();
});

$matched = $matched ?: route('PATCH', '/api/traffic/incidents/:id/resolve', function (string $id) {
    (new IncidentController())->resolve((int) $id);
});

// ── 404 ───────────────────────────────────────────────────────────────────
if (!$matched) {
    http_response_code(404);
    echo json_encode([
        'status'    => 'error',
        'code'      => 404,
        'data'      => null,
        'message'   => 'Endpoint tidak ditemukan',
        'timestamp' => date('c'),
        'service'   => 'traffic-service',
    ]);
}