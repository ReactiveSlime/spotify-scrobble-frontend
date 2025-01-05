<?php
// Database configuration
define('DB_HOST', '');
define('DB_PORT', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

// Error handling
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
