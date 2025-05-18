<?php
/**
 * Payslip Model - Handles database operations for payslips
 */
class Payslip {
    private $conn;
    private $table_name = "payslips";
    
    // Payslip properties
    public $id;
    public $payslip_no;
    public $employee_id;
    public $bank_account_id;
    public $salary;
    public $bonus;
    public $total_salary;
    public $person_in_charge;
    public $cutoff_date;
    public $payment_date;
    public $payment_status;
    public $agent_pdf_path;
    public $admin_pdf_path;
    public $created_at;
    public $updated_at;
    
    // Related data
    public $employee_name;
    public $bank_details;
    
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
     * Get all payslips with pagination
     *
     * @param string $search Search term (optional)
     * @param string $status Payment status filter (optional)
     * @param string $startDate Start date filter (optional)
     * @param string $endDate End date filter (optional)
     * @return PDOStatement Query result
     */
    public function readPaginated($search = '', $status = '', $startDate = '', $endDate = '') {
        $offset = ($this->page - 1) * $this->records_per_page;
        
        $whereConditions = [];
        $bindParams = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(p.payslip_no LIKE :search 
                                  OR p.employee_id LIKE :search 
                                  OR e.firstname LIKE :search
                                  OR e.lastname LIKE :search
                                  OR p.person_in_charge LIKE :search)";
            $bindParams[':search'] = "%{$search}%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = "p.payment_status = :status";
            $bindParams[':status'] = $status;
        }
        
        if (!empty($startDate)) {
            $whereConditions[] = "p.payment_date >= :start_date";
            $bindParams[':start_date'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $whereConditions[] = "p.payment_date <= :end_date";
            $bindParams[':end_date'] = $endDate;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT p.id, p.payslip_no, p.employee_id, p.bank_account_id, p.salary, p.bonus, 
                        p.total_salary, p.person_in_charge, p.cutoff_date, p.payment_date, 
                        p.payment_status, p.agent_pdf_path, p.admin_pdf_path, p.created_at, p.updated_at,
                        e.firstname, e.lastname,
                        b.preferred_bank, b.bank_account_number, b.bank_account_name
                 FROM " . $this->table_name . " p
                 LEFT JOIN employees e ON p.employee_id = e.employee_id
                 LEFT JOIN employee_banking_details b ON p.bank_account_id = b.id
                 {$whereClause} 
                 ORDER BY p.payment_date DESC, p.id DESC
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
     * Count total payslips
     *
     * @param string $search Search term (optional)
     * @param string $status Payment status filter (optional)
     * @param string $startDate Start date filter (optional)
     * @param string $endDate End date filter (optional)
     * @return int Total number of payslips
     */
    public function countAll($search = '', $status = '', $startDate = '', $endDate = '') {
        $whereConditions = [];
        $bindParams = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(p.payslip_no LIKE :search 
                                  OR p.employee_id LIKE :search 
                                  OR e.firstname LIKE :search
                                  OR e.lastname LIKE :search
                                  OR p.person_in_charge LIKE :search)";
            $bindParams[':search'] = "%{$search}%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = "p.payment_status = :status";
            $bindParams[':status'] = $status;
        }
        
        if (!empty($startDate)) {
            $whereConditions[] = "p.payment_date >= :start_date";
            $bindParams[':start_date'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $whereConditions[] = "p.payment_date <= :end_date";
            $bindParams[':end_date'] = $endDate;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT COUNT(*) as total 
                 FROM " . $this->table_name . " p
                 LEFT JOIN employees e ON p.employee_id = e.employee_id
                 LEFT JOIN employee_banking_details b ON p.bank_account_id = b.id
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
     * Get single payslip by ID or payslip number
     *
     * @param string $id Payslip ID or number
     * @param string $type Type of ID ('id' or 'no')
     * @return PDOStatement Query result
     */
    public function readOne($id, $type = 'id') {
        $whereClause = ($type === 'no') ? "p.payslip_no = :id" : "p.id = :id";
        
        $query = "SELECT p.id, p.payslip_no, p.employee_id, p.bank_account_id, p.salary, p.bonus, 
                        p.total_salary, p.person_in_charge, p.cutoff_date, p.payment_date, 
                        p.payment_status, p.agent_pdf_path, p.admin_pdf_path, p.created_at, p.updated_at,
                        e.firstname, e.lastname,
                        b.preferred_bank, b.bank_account_number, b.bank_account_name
                 FROM " . $this->table_name . " p
                 LEFT JOIN employees e ON p.employee_id = e.employee_id
                 LEFT JOIN employee_banking_details b ON p.bank_account_id = b.id
                 WHERE {$whereClause}
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get payslips by employee ID
     *
     * @param string $employeeId Employee ID
     * @return PDOStatement Query result
     */
    public function readByEmployeeId($employeeId) {
        $query = "SELECT p.id, p.payslip_no, p.employee_id, p.bank_account_id, p.salary, p.bonus, 
                        p.total_salary, p.person_in_charge, p.cutoff_date, p.payment_date, 
                        p.payment_status, p.agent_pdf_path, p.admin_pdf_path, p.created_at, p.updated_at,
                        e.firstname, e.lastname,
                        b.preferred_bank, b.bank_account_number, b.bank_account_name
                 FROM " . $this->table_name . " p
                 LEFT JOIN employees e ON p.employee_id = e.employee_id
                 LEFT JOIN employee_banking_details b ON p.bank_account_id = b.id
                 WHERE p.employee_id = :employee_id
                 ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Generate a new payslip number
     *
     * @return string New payslip number
     */
    public function generatePayslipNo() {
        // Get the last payslip number
        $query = "SELECT payslip_no FROM " . $this->table_name . " 
                 ORDER BY id DESC 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Extract number and increment
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastNo = $row['payslip_no'];
            $lastNumber = (int)$lastNo;
            $nextNumber = $lastNumber + 1;
        } else {
            // No existing IDs, start with 1
            $nextNumber = 1;
        }
        
        // Format with leading zeros
        return str_pad($nextNumber, 9, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create new payslip
     *
     * @return bool Whether operation was successful
     */
    public function create() {
        // Generate payslip number if not provided
        if (empty($this->payslip_no)) {
            $this->payslip_no = $this->generatePayslipNo();
        }
        
        // Calculate total salary if not provided
        if (empty($this->total_salary)) {
            $this->total_salary = $this->salary + $this->bonus;
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                (payslip_no, employee_id, bank_account_id, salary, bonus, total_salary, 
                 person_in_charge, cutoff_date, payment_date, payment_status, 
                 agent_pdf_path, admin_pdf_path)
                VALUES
                (:payslip_no, :employee_id, :bank_account_id, :salary, :bonus, :total_salary, 
                 :person_in_charge, :cutoff_date, :payment_date, :payment_status, 
                 :agent_pdf_path, :admin_pdf_path)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->payslip_no = htmlspecialchars(strip_tags($this->payslip_no));
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->bank_account_id = htmlspecialchars(strip_tags($this->bank_account_id));
        $this->salary = (float)$this->salary;
        $this->bonus = (float)$this->bonus;
        $this->total_salary = (float)$this->total_salary;
        $this->person_in_charge = htmlspecialchars(strip_tags($this->person_in_charge));
        $this->cutoff_date = htmlspecialchars(strip_tags($this->cutoff_date));
        $this->payment_date = htmlspecialchars(strip_tags($this->payment_date));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        $this->agent_pdf_path = $this->agent_pdf_path ? htmlspecialchars(strip_tags($this->agent_pdf_path)) : null;
        $this->admin_pdf_path = $this->admin_pdf_path ? htmlspecialchars(strip_tags($this->admin_pdf_path)) : null;
        
        $stmt->bindParam(':payslip_no', $this->payslip_no);
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':bank_account_id', $this->bank_account_id);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':bonus', $this->bonus);
        $stmt->bindParam(':total_salary', $this->total_salary);
        $stmt->bindParam(':person_in_charge', $this->person_in_charge);
        $stmt->bindParam(':cutoff_date', $this->cutoff_date);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':agent_pdf_path', $this->agent_pdf_path);
        $stmt->bindParam(':admin_pdf_path', $this->admin_pdf_path);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Update payslip
     *
     * @return bool Whether operation was successful
     */
    public function update() {
        // Calculate total salary if not provided
        if (empty($this->total_salary)) {
            $this->total_salary = $this->salary + $this->bonus;
        }
        
        $query = "UPDATE " . $this->table_name . "
                SET
                    employee_id = :employee_id,
                    bank_account_id = :bank_account_id,
                    salary = :salary,
                    bonus = :bonus,
                    total_salary = :total_salary,
                    person_in_charge = :person_in_charge,
                    cutoff_date = :cutoff_date,
                    payment_date = :payment_date,
                    payment_status = :payment_status,
                    agent_pdf_path = :agent_pdf_path,
                    admin_pdf_path = :admin_pdf_path
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->bank_account_id = htmlspecialchars(strip_tags($this->bank_account_id));
        $this->salary = (float)$this->salary;
        $this->bonus = (float)$this->bonus;
        $this->total_salary = (float)$this->total_salary;
        $this->person_in_charge = htmlspecialchars(strip_tags($this->person_in_charge));
        $this->cutoff_date = htmlspecialchars(strip_tags($this->cutoff_date));
        $this->payment_date = htmlspecialchars(strip_tags($this->payment_date));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        $this->agent_pdf_path = $this->agent_pdf_path ? htmlspecialchars(strip_tags($this->agent_pdf_path)) : null;
        $this->admin_pdf_path = $this->admin_pdf_path ? htmlspecialchars(strip_tags($this->admin_pdf_path)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':bank_account_id', $this->bank_account_id);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':bonus', $this->bonus);
        $stmt->bindParam(':total_salary', $this->total_salary);
        $stmt->bindParam(':person_in_charge', $this->person_in_charge);
        $stmt->bindParam(':cutoff_date', $this->cutoff_date);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':agent_pdf_path', $this->agent_pdf_path);
        $stmt->bindParam(':admin_pdf_path', $this->admin_pdf_path);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Update only PDF paths
     *
     * @return bool Whether operation was successful
     */
    public function updatePDFPaths() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    agent_pdf_path = :agent_pdf_path,
                    admin_pdf_path = :admin_pdf_path
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->agent_pdf_path = $this->agent_pdf_path ? htmlspecialchars(strip_tags($this->agent_pdf_path)) : null;
        $this->admin_pdf_path = $this->admin_pdf_path ? htmlspecialchars(strip_tags($this->admin_pdf_path)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':agent_pdf_path', $this->agent_pdf_path);
        $stmt->bindParam(':admin_pdf_path', $this->admin_pdf_path);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete payslip
     *
     * @return bool Whether operation was successful
     */
    public function delete() {
        // First, get the PDF paths to delete the files
        $query = "SELECT agent_pdf_path, admin_pdf_path FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete the PDF files if they exist
            if (!empty($row['agent_pdf_path']) && file_exists(FRONTEND_PATH . $row['agent_pdf_path'])) {
                unlink(FRONTEND_PATH . $row['agent_pdf_path']);
            }
            
            if (!empty($row['admin_pdf_path']) && file_exists(FRONTEND_PATH . $row['admin_pdf_path'])) {
                unlink(FRONTEND_PATH . $row['admin_pdf_path']);
            }
        }
        
        // Now delete the record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Get payment statuses
     *
     * @return array Array of available payment statuses
     */
    public function getPaymentStatuses() {
        return [
            'Paid',
            'Pending',
            'Cancelled'
        ];
    }
}