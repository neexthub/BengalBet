<?php
/**
 * BengalBet DB Server Configuration
 * Target Server: https://deserver.neexthub.site/dbserver/
 */

// Report all errors in development or hide in production
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Database Credentials
// Update these with your live MySQL server details if different
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'neexthub_bengalbet');
define('DB_USER', getenv('DB_USER') ?: 'neexthub_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Master Secret Key for administrative file & database control
define('MASTER_API_KEY', getenv('MASTER_API_KEY') ?: '');

// Base filesystem path for file management operations (default: current dbserver folder or web root)
define('SERVER_ROOT_PATH', dirname(__DIR__)); // Parent directory (web root)
define('DBSERVER_PATH', __DIR__); // dbserver directory

// Cross-Origin Resource Sharing (CORS) Configuration
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Master-Key, Origin, Accept");
header("Access-Control-Max-Age: 86400");

// Handle HTTP preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
