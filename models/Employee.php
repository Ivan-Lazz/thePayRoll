<?php
/**
 * Employee Model - Handles database operations for employees
 */
class Employee {
    private $conn;
    private $table_name = "employees";
    
    // Employee properties
    public $employee_id;
    public $firstname;
    public $lastname;
    public $contact_number;
    public $email;
    public $created_at;
    public $updated_at;
    
    // Pagination properties
    public $page;
    public $records_per_page;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all employees with pagination
     *
     * @param string $search Search term (optional)
     * @return PDOStatement Query result
     */
    public function readPaginated($search = '') {
        $offset = ($this->page - 1) * $this->records_per_page;
        
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE employee_id LIKE :search 
                           OR firstname LIKE :search 
                           OR lastname LIKE :search
                           OR email LIKE :search
                           OR contact_number LIKE :search";
        }
        
        $query = "SELECT employee_id, firstname, lastname, contact_number, email, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 {$whereClause} 
                 ORDER BY employee_id ASC 
                 LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $this->records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Count total employees
     *
     * @param string $search Search term (optional)
     * @return int Total number of employees
     */
    public function countAll($search = '') {
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE employee_id LIKE :search 
                           OR firstname LIKE :search 
                           OR lastname LIKE :search
                           OR email LIKE :search
                           OR contact_number LIKE :search";
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " {$whereClause}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }
    
    /**
     * Get single employee by ID
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function readOne($employeeId) {
        $query = "SELECT employee_id, firstname, lastname, contact_number, email, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 WHERE employee_id = :employee_id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Generate a new employee ID
     *
     * @return string New employee ID
     */
    public function generateEmployeeId() {
        $year = date('Y');
        
        // Get the last employee ID for the current year
        $query = "SELECT employee_id FROM " . $this->table_name . " 
                 WHERE employee_id LIKE :year_prefix 
                 ORDER BY employee_id DESC 
                 LIMIT 0,1";
        
        $yearPrefix = $year . '%';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year_prefix', $yearPrefix);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Extract number and increment
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $row['employee_id'];
            $lastNumber = (int)substr($lastId, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            // No existing IDs for this year, start with 1
            $nextNumber = 1;
        }
        
        // Format with leading zeros
        $nextNumberPadded = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        return $year . $nextNumberPadded;
    }
    
    /**
     * Create new employee
     *
     * @return bool Whether operation was successful
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (employee_id, firstname, lastname, contact_number, email)
                VALUES
                (:employee_id, :firstname, :lastname, :contact_number, :email)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Generate employee ID if not provided
        if (empty($this->employee_id)) {
            $this->employee_id = $this->generateEmployeeId();
        }
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':contact_number', $this->contact_number);
        $stmt->bindParam(':email', $this->email);
        
        return $stmt->execute();
    }
    
    /**
     * Update employee
     *
     * @return bool Whether operation was successful
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    firstname = :firstname,
                    lastname = :lastname,
                    contact_number = :contact_number,
                    email = :email
                WHERE employee_id = :employee_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':contact_number', $this->contact_number);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':employee_id', $this->employee_id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete employee
     *
     * @return bool Whether operation was successful
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE employee_id = :employee_id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $stmt->bindParam(':employee_id', $this->employee_id);
        
        return $stmt->execute();
    }
    
    /**
     * Check if employee exists
     *
     * @param string $employeeId Employee ID
     * @return bool Whether employee exists
     */
    public function employeeExists($employeeId) {
        $query = "SELECT employee_id FROM " . $this->table_name . " 
                 WHERE employee_id = :employee_id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get employee accounts
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function getAccounts($employeeId) {
        $query = "SELECT account_id, employee_id, account_email, account_type, account_status
                 FROM employee_accounts
                 WHERE employee_id = :employee_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get employee banking details
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function getBankingDetails($employeeId) {
        $query = "SELECT id, employee_id, preferred_bank, bank_account_number, bank_account_name
                 FROM employee_banking_details
                 WHERE employee_id = :employee_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
}