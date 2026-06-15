<?php

define('ROOT', dirname(__DIR__));

// Load environment variables dari .env
if (file_exists(ROOT . '/.env')) {
    $lines = file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Autoload via Composer
require ROOT . '/vendor/autoload.php';

// Header JSON untuk semua response
header('Content-Type: application/json');
header('X-Service: traffic-service');

// Load routes
require ROOT . '/routes/api.php';