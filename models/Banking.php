<?php
/**
 * Banking Model - Handles database operations for employee banking details
 */
class Banking {
    private $conn;
    private $table_name = "employee_banking_details";
    
    // Banking properties
    public $id;
    public $employee_id;
    public $preferred_bank;
    public $bank_account_number;
    public $bank_account_name;
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
     * Get all banking details with pagination
     *
     * @param string $search Search term (optional)
     * @return PDOStatement Query result
     */
    public function readPaginated($search = '') {
        $offset = ($this->page - 1) * $this->records_per_page;
        
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE b.id LIKE :search 
                           OR b.employee_id LIKE :search 
                           OR b.preferred_bank LIKE :search
                           OR b.bank_account_number LIKE :search
                           OR b.bank_account_name LIKE :search
                           OR e.firstname LIKE :search
                           OR e.lastname LIKE :search";
        }
        
        $query = "SELECT b.id, b.employee_id, b.preferred_bank, b.bank_account_number, b.bank_account_name, 
                        b.created_at, b.updated_at,
                        e.firstname, e.lastname
                 FROM " . $this->table_name . " b
                 LEFT JOIN employees e ON b.employee_id = e.employee_id
                 {$whereClause} 
                 ORDER BY b.id ASC 
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
     * Count total banking details
     *
     * @param string $search Search term (optional)
     * @return int Total number of banking details
     */
    public function countAll($search = '') {
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE b.id LIKE :search 
                           OR b.employee_id LIKE :search 
                           OR b.preferred_bank LIKE :search
                           OR b.bank_account_number LIKE :search
                           OR b.bank_account_name LIKE :search
                           OR e.firstname LIKE :search
                           OR e.lastname LIKE :search";
        }
        
        $query = "SELECT COUNT(*) as total 
                 FROM " . $this->table_name . " b
                 LEFT JOIN employees e ON b.employee_id = e.employee_id
                 {$whereClause}";
        
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
     * Get single banking detail by ID
     *
     * @param int $id Banking detail ID
     * @return PDOStatement Query result
     */
    public function readOne($id) {
        $query = "SELECT b.id, b.employee_id, b.preferred_bank, b.bank_account_number, b.bank_account_name, 
                        b.created_at, b.updated_at,
                        e.firstname, e.lastname
                 FROM " . $this->table_name . " b
                 LEFT JOIN employees e ON b.employee_id = e.employee_id
                 WHERE b.id = :id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get banking details by employee ID
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function readByEmployeeId($employeeId) {
        $query = "SELECT id, employee_id, preferred_bank, bank_account_number, bank_account_name, 
                        created_at, updated_at
                 FROM " . $this->table_name . " 
                 WHERE employee_id = :employee_id
                 ORDER BY id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Create new banking detail
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
                (employee_id, preferred_bank, bank_account_number, bank_account_name)
                VALUES
                (:employee_id, :preferred_bank, :bank_account_number, :bank_account_name)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->preferred_bank = htmlspecialchars(strip_tags($this->preferred_bank));
        $this->bank_account_number = htmlspecialchars(strip_tags($this->bank_account_number));
        $this->bank_account_name = htmlspecialchars(strip_tags($this->bank_account_name));
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':preferred_bank', $this->preferred_bank);
        $stmt->bindParam(':bank_account_number', $this->bank_account_number);
        $stmt->bindParam(':bank_account_name', $this->bank_account_name);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Update banking detail
     *
     * @return bool Whether operation was successful
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    employee_id = :employee_id,
                    preferred_bank = :preferred_bank,
                    bank_account_number = :bank_account_number,
                    bank_account_name = :bank_account_name
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->preferred_bank = htmlspecialchars(strip_tags($this->preferred_bank));
        $this->bank_account_number = htmlspecialchars(strip_tags($this->bank_account_number));
        $this->bank_account_name = htmlspecialchars(strip_tags($this->bank_account_name));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':preferred_bank', $this->preferred_bank);
        $stmt->bindParam(':bank_account_number', $this->bank_account_number);
        $stmt->bindParam(':bank_account_name', $this->bank_account_name);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete banking detail
     *
     * @return bool Whether operation was successful
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Check if banking detail exists for employee and account
     *
     * @param string $employeeId Employee ID
     * @param string $accountNumber Account number
     * @return bool Whether banking detail exists
     */
    public function bankingDetailExists($employeeId, $accountNumber) {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE employee_id = :employee_id 
                 AND bank_account_number = :bank_account_number
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->bindParam(':bank_account_number', $accountNumber);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}