<?php
/**
 * Database configuration.
 *
 * Values come from environment variables (set in docker-compose.yml /
 * .env), so no credentials live in the source code. The defaults below
 * match the docker-compose service name and are only a fallback.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'avalon_db');
define('DB_USER', getenv('DB_USER') ?: 'avalon_user');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements -> prevents SQL injection
        ]
    );
} catch (PDOException $e) {
    // Never leak connection details/exception messages to the browser.
    error_log('DB connection failed: ' . $e->getMessage()); // visible in `docker compose logs web`
    http_response_code(500);
    die('Service temporarily unavailable. Please try again later.');
}
