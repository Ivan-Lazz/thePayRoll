<?php
// Application configuration

// Environment settings - change to 'production' for live site
define('APP_ENV', 'development');

// Base paths and URLs
define('BASE_PATH', dirname(__DIR__));
define('API_URL', '/testPayRoll/api');

// Frontend URL (change this according to your frontend deployment)
define('FRONTEND_URL', 'http://localhost:3000'); // Adjust this for your frontend URL

// Security settings
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your_strong_secret_key_here_change_in_production');
define('JWT_EXPIRY', 3600); // Token validity in seconds (1 hour)
define('PASSWORD_COST', 12); // Cost factor for password_hash
define('SESSION_TIMEOUT', 1800); // Session timeout in seconds (30 minutes)

// CORS settings
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost:3000',    // Local development
    'http://localhost:5173',    // Vite default port
    'http://127.0.0.1:3000',
    // Add your production frontend URL here
]);

// CSRF Protection
define('CSRF_ENABLED', true);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// PDF Generation settings
define('COMPANY_NAME', 'Your Company');
define('COMPANY_LOGO', BASE_PATH . '/assets/img/logo.png');

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Application settings
define('PDF_PATH', BASE_PATH . '/pdfs');
define('AGENT_PDF_PATH', PDF_PATH . '/agent');
define('ADMIN_PDF_PATH', PDF_PATH . '/admin');

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Initialize required directories
function initDirectories() {
    $directories = [
        PDF_PATH,
        AGENT_PDF_PATH,
        ADMIN_PDF_PATH,
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Create .htaccess to protect PDF directories
    $htaccess = "Order Deny,Allow\nDeny from all\n";
    file_put_contents(PDF_PATH . '/.htaccess', $htaccess);
}

// Initialize directories when config is loaded
initDirectories();