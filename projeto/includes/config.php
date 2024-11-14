<?php
// Database configuration
define('DB_HOST', 'localhost:8080');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce');

// Site configuration
define('SITE_NAME', 'Minha Loja');
define('SITE_URL', 'http://localhost/projeto');
define('ADMIN_EMAIL', 'admin@exemplo.com');

// Session configuration
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('America/Sao_Paulo');