<?php
// includes/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'eden_terrace');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Eden Terrace Hotel & Restaurant');
define('SITE_URL', 'http://localhost/eden-terrace');
define('ADMIN_EMAIL', 'admin@edenterrace.com');

// Timezone
date_default_timezone_set('America/New_York');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone for PHP warnings
date_default_timezone_set('UTC');
?>