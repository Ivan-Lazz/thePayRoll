<?php
// debug-login.php - Test login process specifically
header('Content-Type: text/html');

// Temporarily enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'utils/ResponseHandler.php';
require_once 'utils/InputValidator.php';
require_once 'utils/TokenManager.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/CSRFMiddleware.php';
require_once 'models/User.php';
require_once 'controllers/AuthController.php';

// Simulate login request
try {
    // Initialize database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create an auth controller
    $controller = new AuthController($conn);
    
    // Set up POST data for login
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'username' => 'admin',
        'password' => 'Admin@123'
    ];
    
    // Buffer output to capture response
    ob_start();
    
    // Simulate login request (this should output JSON)
    $controller->handleRequest('POST', 'login');
    
    // Get the output
    $output = ob_get_clean();
    
    // Display results
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Debug Login</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            h1 { color: #ff8c00; }
            .box { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
            pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        </style>
    </head>
    <body>
        <h1>Debug Login Results</h1>
        
        <div class='box'>
            <h2>Configuration Check</h2>";
            
    // Check for required constants
    $requiredConstants = ['JWT_SECRET', 'JWT_EXPIRY', 'CSRF_TOKEN_EXPIRY'];
    echo "<h3>Required Constants:</h3><ul>";
    foreach ($requiredConstants as $const) {
        if (defined($const)) {
            echo "<li style='color:green'>$const is defined: " . constant($const) . "</li>";
        } else {
            echo "<li style='color:red'>$const is NOT defined</li>";
        }
    }
    echo "</ul>";
    
    // Check database connection
    echo "<h3>Database Connection:</h3>";
    try {
        $testConn = $db->getConnection();
        echo "<p style='color:green'>Database connection successful!</p>";
        
        // Check if admin user exists
        $query = "SELECT COUNT(*) FROM users WHERE username = 'admin'";
        $stmt = $testConn->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "<p style='color:green'>Admin user exists in database!</p>";
        } else {
            echo "<p style='color:red'>Admin user does NOT exist in database!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
    }
        
    echo "</div>
        
        <div class='box'>
            <h2>Login Response</h2>
            <p>Content-Type: " . (strpos($output, '{') === 0 ? "JSON (Good)" : "Not JSON (Error)") . "</p>
            <pre>" . htmlspecialchars($output) . "</pre>
        </div>
        
        <div class='box'>
            <h2>What to Check</h2>
            <ol>
                <li>Make sure all required constants are defined in config.php</li>
                <li>Ensure the database connection is working</li>
                <li>Check that the admin user exists in the database</li>
                <li>Verify the login response is valid JSON</li>
                <li>If you see PHP errors, fix those specific issues</li>
            </ol>
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}