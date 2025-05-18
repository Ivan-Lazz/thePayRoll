<?php
/**
 * Account Model - Handles database operations for employee accounts
 */
class Account {
    private $conn;
    private $table_name = "employee_accounts";
    
    // Account properties
    public $account_id;
    public $employee_id;
    public $account_email;
    public $account_password;
    public $account_type;
    public $account_status;
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
     * Get all accounts with pagination
     *
     * @param string $search Search term (optional)
     * @param string $type Account type filter (optional)
     * @return PDOStatement Query result
     */
    public function readPaginated($search = '', $type = '') {
        $offset = ($this->page - 1) * $this->records_per_page;
        
        $whereConditions = [];
        $bindParams = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(a.account_id LIKE :search 
                                  OR a.account_email LIKE :search 
                                  OR a.employee_id LIKE :search
                                  OR e.firstname LIKE :search
                                  OR e.lastname LIKE :search)";
            $bindParams[':search'] = "%{$search}%";
        }
        
        if (!empty($type)) {
            $whereConditions[] = "a.account_type = :type";
            $bindParams[':type'] = $type;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT a.account_id, a.employee_id, a.account_email, a.account_type, a.account_status, 
                        a.created_at, a.updated_at, 
                        e.firstname, e.lastname
                 FROM " . $this->table_name . " a
                 LEFT JOIN employees e ON a.employee_id = e.employee_id 
                 {$whereClause} 
                 ORDER BY a.account_id ASC 
                 LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($bindParams as $param => $value) {
            $stmt->bindParam($param, $value);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $this->records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Count total accounts
     *
     * @param string $search Search term (optional)
     * @param string $type Account type filter (optional)
     * @return int Total number of accounts
     */
    public function countAll($search = '', $type = '') {
        $whereConditions = [];
        $bindParams = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(a.account_id LIKE :search 
                                  OR a.account_email LIKE :search 
                                  OR a.employee_id LIKE :search
                                  OR e.firstname LIKE :search
                                  OR e.lastname LIKE :search)";
            $bindParams[':search'] = "%{$search}%";
        }
        
        if (!empty($type)) {
            $whereConditions[] = "a.account_type = :type";
            $bindParams[':type'] = $type;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT COUNT(*) as total 
                 FROM " . $this->table_name . " a
                 LEFT JOIN employees e ON a.employee_id = e.employee_id 
                 {$whereClause}";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($bindParams as $param => $value) {
            $stmt->bindParam($param, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }
    
    /**
     * Get single account by ID
     *
     * @param int $accountId Account ID
     * @return PDOStatement Query result
     */
    public function readOne($accountId) {
        $query = "SELECT a.account_id, a.employee_id, a.account_email, a.account_type, a.account_status, 
                        a.created_at, a.updated_at, 
                        e.firstname, e.lastname
                 FROM " . $this->table_name . " a
                 LEFT JOIN employees e ON a.employee_id = e.employee_id 
                 WHERE a.account_id = :account_id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':account_id', $accountId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get accounts by employee ID
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function readByEmployeeId($employeeId) {
        $query = "SELECT account_id, employee_id, account_email, account_type, account_status, created_at, updated_at
                 FROM " . $this->table_name . " 
                 WHERE employee_id = :employee_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Create new account
     *
     * @return bool Whether operation was successful
     */
    public function create() {
        // Check if employee exists
        $employeeModel = new Employee($this->conn);
        if (!$employeeModel->employeeExists($this->employee_id)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                (employee_id, account_email, account_password, account_type, account_status)
                VALUES
                (:employee_id, :account_email, :account_password, :account_type, :account_status)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->account_email = htmlspecialchars(strip_tags($this->account_email));
        $this->account_type = htmlspecialchars(strip_tags($this->account_type));
        $this->account_status = htmlspecialchars(strip_tags($this->account_status ?? 'ACTIVE'));
        
        // Hash password
        $password_hash = password_hash($this->account_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':account_email', $this->account_email);
        $stmt->bindParam(':account_password', $password_hash);
        $stmt->bindParam(':account_type', $this->account_type);
        $stmt->bindParam(':account_status', $this->account_status);
        
        if ($stmt->execute()) {
            $this->account_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Update account
     *
     * @return bool Whether operation was successful
     */
    public function update() {
        // Determine if password needs to be updated
        $passwordSet = !empty($this->account_password);
        $passwordClause = $passwordSet ? ", account_password = :account_password" : "";
        
        $query = "UPDATE " . $this->table_name . "
                SET
                    employee_id = :employee_id,
                    account_email = :account_email,
                    account_type = :account_type,
                    account_status = :account_status" . 
                    $passwordClause . "
                WHERE account_id = :account_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->account_email = htmlspecialchars(strip_tags($this->account_email));
        $this->account_type = htmlspecialchars(strip_tags($this->account_type));
        $this->account_status = htmlspecialchars(strip_tags($this->account_status));
        $this->account_id = htmlspecialchars(strip_tags($this->account_id));
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':account_email', $this->account_email);
        $stmt->bindParam(':account_type', $this->account_type);
        $stmt->bindParam(':account_status', $this->account_status);
        $stmt->bindParam(':account_id', $this->account_id);
        
        // Bind password if it's being updated
        if ($passwordSet) {
            $password_hash = password_hash($this->account_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $stmt->bindParam(':account_password', $password_hash);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Delete account
     *
     * @return bool Whether operation was successful
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE account_id = :account_id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->account_id = htmlspecialchars(strip_tags($this->account_id));
        $stmt->bindParam(':account_id', $this->account_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get account types
     *
     * @return array Array of available account types
     */
    public function getAccountTypes() {
        return [
            'Team Leader',
            'Overflow',
            'Auto-Warranty',
            'Commissions'
        ];
    }
    
    /**
     * Get account statuses
     *
     * @return array Array of available account statuses
     */
    public function getAccountStatuses() {
        return [
            'ACTIVE',
            'INACTIVE',
            'SUSPENDED'
        ];
    }
}