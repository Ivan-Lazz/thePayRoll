<?php
class Database {
    private $host = "localhost";
    private $db_name = "bm_payroll"; // Make sure this database exists
    private $username = "root";      // Update if needed
    private $password = "";          // Update if needed
    private $conn;
    private $options;

    public function __construct() {
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
    }

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                $this->options
            );
            return $this->conn;
        } catch (PDOException $e) {
            $this->logError($e);
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    // Rest of the class remains the same
    // ...
}