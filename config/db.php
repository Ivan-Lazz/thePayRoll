<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $options;
    
    public function __construct() {
        // Get database configuration from environment variables with fallbacks
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = getenv('DB_PORT') ?: '3306';
        $this->db_name = getenv('DB_NAME') ?: 'bm_payroll';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
    }
    
    public function getConnection() {
        try {
            if ($this->conn === null) {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                    $this->host,
                    $this->port,
                    $this->db_name
                );
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
            }
            return $this->conn;
        } catch (PDOException $e) {
            $this->logError($e);
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    // For debugging purposes
    public function getConnectionStatus() {
        try {
            $this->getConnection();
            return "Connected to database: " . $this->db_name;
        } catch (Exception $e) {
            $this->logError($e);
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has required permissions
     */
    private function checkPermissions($conn) {
        try {
            // Test CREATE permission
            $conn->exec("CREATE TABLE IF NOT EXISTS `permission_test` (id INT)");
            $conn->exec("DROP TABLE IF EXISTS `permission_test`");
            
            // Test ALTER permission
            $conn->exec("CREATE TABLE IF NOT EXISTS `permission_test` (id INT)");
            $conn->exec("ALTER TABLE `permission_test` ADD COLUMN test VARCHAR(10)");
            $conn->exec("DROP TABLE IF EXISTS `permission_test`");
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Insufficient database permissions. User needs CREATE, ALTER, and DROP permissions.");
        }
    }

    /**
     * Creates the database and tables if they don't exist
     * Used during installation
     */
    public function initializeDatabase() {
        try {
            // First connect without selecting a database
            $tempConn = new PDO(
                sprintf("mysql:host=%s;port=%s;charset=utf8mb4", $this->host, $this->port),
                $this->username,
                $this->password,
                $this->options
            );
            
            // Check permissions before proceeding
            $this->checkPermissions($tempConn);
            
            // Create database if not exists
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}`
                            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            
            // Select the database
            $tempConn->exec("USE `{$this->db_name}`");
            
            // Create version table first
            $this->createVersionTable($tempConn);
            
            // Now create other tables
            $this->createTables($tempConn);
            
            // Store the connection for future use
            $this->conn = $tempConn;
            
            return true;
        } catch (PDOException $e) {
            $this->logError($e);
            error_log("Database initialization failed: " . $e->getMessage());
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create version tracking table
     */
    private function createVersionTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS `schema_version` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `version` VARCHAR(50) NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `description` TEXT,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($sql);
    }
    
    /**
     * Update schema version
     */
    private function updateSchemaVersion($conn, $version, $description) {
        $stmt = $conn->prepare("INSERT INTO `schema_version` (version, description) VALUES (?, ?)");
        $stmt->execute([$version, $description]);
    }

    /**
     * Create all required tables
     */
    private function createTables($conn) {
        // Get SQL from schema file
        $schemaPath = dirname(__DIR__) . '/install/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema file not found: $schemaPath");
        }
        
        $sql = file_get_contents($schemaPath);
        
        if (empty($sql)) {
            throw new Exception("Schema file is empty");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $conn->exec($statement);
                }
            }
            
            // Record schema version
            $this->updateSchemaVersion($conn, '1.0.0', 'Initial schema installation');
            
            // Commit transaction
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Log database errors
     */
    private function logError($exception) {
        $logDir = dirname(__DIR__) . '/logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Log error
        $logFile = $logDir . '/db_error.log';
        $message = date('[Y-m-d H:i:s]') . ' ' . $exception->getMessage() .
                 ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() . "\n";
        
        error_log($message, 3, $logFile);
    }
}