<?php
// Basic settings
define('DB_HOST', 'localhost:3307');  // Porta MySQL
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_NAME', 'ecommerce');

// Site configuration
define('SITE_NAME', 'Minha Loja');
define('SITE_URL', 'http://localhost:81/projeto'); // Porta Apache
define('ADMIN_EMAIL', 'admin@exemplo.com');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30);

// Time zone
date_default_timezone_set('America/Sao_Paulo');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}