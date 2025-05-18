<?php
// This script should be placed in your root directory to test API connectivity

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic information about server environment
echo "<h1>PaySlip Generator API Diagnostic</h1>";
echo "<h2>Server Information</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Script Path: " . __FILE__ . "\n";
echo "Installation Directory: " . dirname(__FILE__) . "\n";
echo "</pre>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once __DIR__ . '/config/db.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color:green;'>✓ Database connection successful</p>";
        
        // Check if required tables exist
        $tables = ['users', 'employees', 'employee_accounts', 'employee_banking_details', 'payslips'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $query = "SELECT 1 FROM $table LIMIT 1";
            try {
                $stmt = $conn->prepare($query);
                $stmt->execute();
                echo "<p style='color:green;'>✓ Table '$table' exists</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>✗ Table '$table' does not exist or cannot be accessed</p>";
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            echo "<p style='color:orange;'>Some tables are missing. Do you need to run the installer?</p>";
            echo "<p>You can visit <a href='/testPayRoll/api/install' target='_blank'>/testPayRoll/api/install</a> to create the database tables.</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database connection error: " . $e->getMessage() . "</p>";
}

// Check directory permissions
echo "<h2>Directory Permissions</h2>";
$directories = [
    '/' => dirname(__FILE__),
    '/config' => dirname(__FILE__) . '/config',
    '/logs' => dirname(__FILE__) . '/logs',
    '/pdfs' => dirname(__FILE__) . '/../frontend/pdfs',
    '/pdfs/agent' => dirname(__FILE__) . '/../frontend/pdfs/agent',
    '/pdfs/admin' => dirname(__FILE__) . '/../frontend/pdfs/admin',
];

foreach ($directories as $name => $path) {
    if (file_exists($path)) {
        if (is_writable($path)) {
            echo "<p style='color:green;'>✓ Directory $name is writable</p>";
        } else {
            echo "<p style='color:red;'>✗ Directory $name is not writable</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ Directory $name does not exist</p>";
    }
}

// Test API endpoints
echo "<h2>API Endpoint Test</h2>";
$apiEndpoints = [
    'auth/check' => 'GET',
    'employees' => 'GET',
    'auth/login' => 'POST',
];

foreach ($apiEndpoints as $endpoint => $method) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/testPayRoll/api/" . $endpoint;
    
    echo "<h3>Testing: $method $url</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($endpoint === 'auth/login') {
            // Test with default admin credentials
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'username' => 'admin',
                'password' => 'Admin@123'
            ]));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "<p>Status code: $httpCode</p>";
    echo "<p>Response headers:</p>";
    echo "<pre>" . htmlspecialchars($headers) . "</pre>";
    echo "<p>Response body:</p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
    
    curl_close($ch);
}

// CORS test from front-end perspective
echo "<h2>CORS Test</h2>";
echo '<div id="corsResult">Testing CORS with JavaScript fetch...</div>';
echo '<script>
    // Test CORS by making a fetch request to the API
    fetch("http://" + window.location.hostname + "/testPayRoll/api/auth/check", {
        method: "GET",
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(response => {
        return response.text().then(text => {
            return {
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                body: text
            };
        });
    })
    .then(data => {
        document.getElementById("corsResult").innerHTML = 
            "<p>CORS Test Result:</p>" +
            "<p>Status: " + data.status + " " + data.statusText + "</p>" +
            "<p>Response Body:</p>" +
            "<pre>" + data.body + "</pre>";
    })
    .catch(error => {
        document.getElementById("corsResult").innerHTML = 
            "<p style=\'color:red;\'>CORS Test Failed: " + error.message + "</p>" +
            "<p>This likely indicates a CORS configuration issue.</p>";
    });
</script>';

// Create a simple tool to test Path Resolution
echo "<h2>Path Resolution Test</h2>";
echo "<p>This tool helps identify if your API routes are being correctly interpreted by the server.</p>";

echo '<form id="pathForm" style="margin-bottom: 20px;">
    <label for="testPath">Test Path:</label>
    <input type="text" id="testPath" name="testPath" value="/testPayRoll/api/employees" style="width: 300px; margin-right: 10px;">
    <button type="submit">Test</button>
</form>';

echo '<div id="pathResult"></div>';

echo '<script>
document.getElementById("pathForm").addEventListener("submit", function(e) {
    e.preventDefault();
    var path = document.getElementById("testPath").value;
    
    fetch(path, {
        method: "GET",
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(response => {
        return response.text().then(text => {
            return {
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                body: text
            };
        });
    })
    .then(data => {
        document.getElementById("pathResult").innerHTML = 
            "<p>Path Test Result:</p>" +
            "<p>Status: " + data.status + " " + data.statusText + "</p>" +
            "<p>Response Body:</p>" +
            "<pre>" + data.body + "</pre>";
    })
    .catch(error => {
        document.getElementById("pathResult").innerHTML = 
            "<p style=\'color:red;\'>Path Test Failed: " + error.message + "</p>";
    });
});
</script>';

// File includes check
echo "<h2>Required Files Check</h2>";
$requiredFiles = [
    '/config/config.php',
    '/config/db.php',
    '/controllers/BaseController.php',
    '/controllers/AuthController.php',
    '/controllers/EmployeeController.php',
    '/controllers/PayslipController.php',
    '/utils/ResponseHandler.php',
    '/utils/TokenManager.php',
    '/middleware/AuthMiddleware.php',
    '/middleware/CSRFMiddleware.php',
    '/.htaccess',
    '/index.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = dirname(__FILE__) . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color:green;'>✓ File $file exists</p>";
    } else {
        echo "<p style='color:red;'>✗ File $file is missing</p>";
    }
}

// Server module check
echo "<h2>Server Module Check</h2>";
$requiredModules = [
    'PDO',
    'PDO_MySQL',
    'mod_rewrite' => 'mod_rewrite (Apache)',
    'json' => 'JSON',
    'session' => 'Session'
];

foreach ($requiredModules as $key => $module) {
    $modName = is_numeric($key) ? $module : $key;
    $displayName = is_numeric($key) ? $module : $module;
    
    if ($modName == 'mod_rewrite') {
        // Check for mod_rewrite indirectly
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            if (in_array('mod_rewrite', $modules)) {
                echo "<p style='color:green;'>✓ $displayName is enabled</p>";
            } else {
                echo "<p style='color:red;'>✗ $displayName is not enabled. This is required for URL routing.</p>";
            }
        } else {
            echo "<p style='color:orange;'>? Unable to check for $displayName (not running under Apache module)</p>";
        }
    } else {
        // Check PHP extensions
        if (extension_loaded($modName)) {
            echo "<p style='color:green;'>✓ $displayName extension is loaded</p>";
        } else {
            echo "<p style='color:red;'>✗ $displayName extension is not loaded. This is required for the application.</p>";
        }
    }
}