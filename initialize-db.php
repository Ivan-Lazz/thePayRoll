<?php
// initialize-database.php - Create database and tables
header('Content-Type: text/html');

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$host = "localhost";
$username = "root";
$password = "";
$dbname = "bm_payroll";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Initialize Database</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #ff8c00; }
        .success { color: green; }
        .error { color: red; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f8f8f8; }
        pre { background: #f0f0f0; padding: 10px; overflow: auto; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #ff8c00; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Database Initialization</h1>";

try {
    echo "<div class='step'>
        <h2>Step 1: Connect to MySQL</h2>";
    
    // Connect to MySQL without selecting a database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✓ Connected to MySQL server successfully!</p>
    </div>";
    
    // Step 2: Create database if it doesn't exist
    echo "<div class='step'>
        <h2>Step 2: Create Database</h2>";
    
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    $conn->exec($sql);
    
    echo "<p class='success'>✓ Database '$dbname' created or already exists!</p>
    </div>";
    
    // Step 3: Select database
    echo "<div class='step'>
        <h2>Step 3: Select Database</h2>";
    
    $conn->exec("USE `$dbname`");
    
    echo "<p class='success'>✓ Database '$dbname' selected!</p>
    </div>";
    
    // Step 4: Create tables
    echo "<div class='step'>
        <h2>Step 4: Create Tables</h2>";
    
    // Define SQL for tables
    $tableSQL = "
    -- Table structure for users
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `firstname` varchar(100) NOT NULL,
      `lastname` varchar(100) NOT NULL,
      `username` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      `email` varchar(100) DEFAULT NULL,
      `role` varchar(50) DEFAULT 'user',
      `status` varchar(20) DEFAULT 'active',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Table structure for employees
    CREATE TABLE IF NOT EXISTS `employees` (
      `employee_id` varchar(20) NOT NULL,
      `firstname` varchar(100) NOT NULL,
      `lastname` varchar(100) NOT NULL,
      `contact_number` varchar(50) NOT NULL,
      `email` varchar(100) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Table structure for employee accounts
    CREATE TABLE IF NOT EXISTS `employee_accounts` (
      `account_id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` varchar(20) NOT NULL,
      `account_email` varchar(150) NOT NULL,
      `account_password` varchar(255) NOT NULL,
      `account_type` varchar(50) NOT NULL,
      `account_status` varchar(50) NOT NULL DEFAULT 'ACTIVE',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`account_id`),
      KEY `employee_id` (`employee_id`),
      CONSTRAINT `employee_accounts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Table structure for employee banking details
    CREATE TABLE IF NOT EXISTS `employee_banking_details` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` varchar(20) NOT NULL,
      `preferred_bank` varchar(150) NOT NULL,
      `bank_account_number` varchar(100) NOT NULL,
      `bank_account_name` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `employee_id` (`employee_id`),
      CONSTRAINT `employee_banking_details_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Table structure for payslips
    CREATE TABLE IF NOT EXISTS `payslips` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `payslip_no` varchar(20) NOT NULL,
      `employee_id` varchar(20) NOT NULL,
      `bank_account_id` int(11) NOT NULL,
      `salary` decimal(10,2) NOT NULL,
      `bonus` decimal(10,2) NOT NULL,
      `total_salary` decimal(10,2) NOT NULL,
      `person_in_charge` varchar(100) NOT NULL,
      `cutoff_date` date NOT NULL,
      `payment_date` date NOT NULL,
      `payment_status` varchar(50) NOT NULL,
      `agent_pdf_path` varchar(255) DEFAULT NULL,
      `admin_pdf_path` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `payslip_no` (`payslip_no`),
      KEY `employee_id` (`employee_id`),
      KEY `bank_account_id` (`bank_account_id`),
      CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
      CONSTRAINT `payslips_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `employee_banking_details` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    // Execute SQL statements separately
    $statements = array_filter(array_map('trim', explode(';', $tableSQL)));
    $tableCount = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $conn->exec($statement);
            $tableCount++;
        }
    }
    
    echo "<p class='success'>✓ Created $tableCount tables successfully!</p>
    </div>";
    
    // Step 5: Insert default admin user
    echo "<div class='step'>
        <h2>Step 5: Create Admin User</h2>";
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    
    if ($adminExists) {
        echo "<p class='success'>✓ Admin user already exists!</p>";
    } else {
        // Create admin user
        $firstname = "Admin";
        $lastname = "User";
        $username = "admin";
        $password = password_hash("Admin@123", PASSWORD_BCRYPT, ['cost' => 12]);
        $email = "admin@example.com";
        $role = "admin";
        $status = "active";
        
        $sql = "INSERT INTO users (firstname, lastname, username, password, email, role, status) 
                VALUES (:firstname, :lastname, :username, :password, :email, :role, :status)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':firstname', $firstname);
        $stmt->bindParam(':lastname', $lastname);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        echo "<p class='success'>✓ Created admin user with username 'admin' and password 'Admin@123'</p>";
    }
    
    echo "</div>";
    
    // Step 6: Check directory structure
    echo "<div class='step'>
        <h2>Step 6: Check Directory Structure</h2>";
    
    $directories = [
        'pdfs',
        'pdfs/agent',
        'pdfs/admin'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p class='success'>✓ Created directory: $dir</p>";
            } else {
                echo "<p class='error'>✗ Failed to create directory: $dir</p>";
            }
        } else {
            if (is_writable($dir)) {
                echo "<p class='success'>✓ Directory exists and is writable: $dir</p>";
            } else {
                echo "<p class='error'>✗ Directory exists but is not writable: $dir</p>";
            }
        }
    }
    
    // Create .htaccess to protect PDF directories
    $htaccess = "Order Deny,Allow\nDeny from all\n";
    if (file_put_contents('pdfs/.htaccess', $htaccess)) {
        echo "<p class='success'>✓ Created .htaccess protection for PDF directories</p>";
    } else {
        echo "<p class='error'>✗ Failed to create .htaccess protection</p>";
    }
    
    echo "</div>";
    
    // Final message
    echo "<div class='step'>
        <h2>Initialization Complete!</h2>
        <p class='success'>The database has been successfully initialized. You can now use the system.</p>
        <p>Admin login credentials:</p>
        <ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> Admin@123</li>
        </ul>
        <p>Important: Please change this default password after your first login!</p>
        <a href='testPayRollFront/pages/index.html' class='btn'>Go to Login Page</a>
    </div>";
    
} catch(PDOException $e) {
    echo "<div class='step'>
        <h2>Error</h2>
        <p class='error'>Database initialization failed: " . $e->getMessage() . "</p>
        <pre>" . $e->getTraceAsString() . "</pre>
    </div>";
}

echo "</body>
</html>";
?>