<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Account.php';
require_once __DIR__ . '/../models/Banking.php';

/**
 * EmployeeController - Handles employee-related API endpoints
 */
class EmployeeController extends BaseController {
    private $employeeModel;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        parent::__construct($db);
        $this->employeeModel = new Employee($db);
    }
    
    /**
     * Get all employees with pagination
     */
    protected function getAll() {
        try {
            // Get pagination and search parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : DEFAULT_PAGE_SIZE;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            // Validate pagination parameters
            $page = max(1, $page);
            $perPage = min(max(1, $perPage), MAX_PAGE_SIZE);
            
            // Set pagination properties in the model
            $this->employeeModel->page = $page;
            $this->employeeModel->records_per_page = $perPage;
            
            // Get employee data
            $stmt = $this->employeeModel->readPaginated($search);
            $employees = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $employees[] = $row;
            }
            
            // Get total count for pagination
            $totalRecords = $this->employeeModel->countAll($search);
            
            // Return paginated response
            ResponseHandler::paginated($employees, $page, $perPage, $totalRecords);
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving employees: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single employee by ID
     *
     * @param string $id Employee ID
     */
    protected function getOne($id) {
        try {
            $stmt = $this->employeeModel->readOne($id);
            
            if ($stmt->rowCount() > 0) {
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get related accounts
                $accountsStmt = $this->employeeModel->getAccounts($id);
                $accounts = [];
                
                while ($accountRow = $accountsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $accounts[] = $accountRow;
                }
                
                // Get related banking details
                $bankingStmt = $this->employeeModel->getBankingDetails($id);
                $bankingDetails = [];
                
                while ($bankingRow = $bankingStmt->fetch(PDO::FETCH_ASSOC)) {
                    $bankingDetails[] = $bankingRow;
                }
                
                // Add related data to response
                $employee['accounts'] = $accounts;
                $employee['banking_details'] = $bankingDetails;
                
                ResponseHandler::success('Employee retrieved successfully', $employee);
            } else {
                ResponseHandler::notFound('Employee not found');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new employee
     */
    protected function create() {
        try {
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['firstname', 'lastname', 'contact_number', 'email'])
                ->email('email');
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Set employee properties
            $this->employeeModel->firstname = $data['firstname'];
            $this->employeeModel->lastname = $data['lastname'];
            $this->employeeModel->contact_number = $data['contact_number'];
            $this->employeeModel->email = $data['email'];
            
            // Generate employee ID if not provided
            if (empty($data['employee_id'])) {
                $this->employeeModel->employee_id = $this->employeeModel->generateEmployeeId();
            } else {
                $this->employeeModel->employee_id = $data['employee_id'];
            }
            
            // Create the employee
            if ($this->employeeModel->create()) {
                $employee = [
                    'employee_id' => $this->employeeModel->employee_id,
                    'firstname' => $this->employeeModel->firstname,
                    'lastname' => $this->employeeModel->lastname,
                    'contact_number' => $this->employeeModel->contact_number,
                    'email' => $this->employeeModel->email
                ];
                
                ResponseHandler::created('Employee created successfully', $employee);
            } else {
                ResponseHandler::serverError('Failed to create employee');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error creating employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing employee
     *
     * @param string $id Employee ID
     */
    protected function update($id) {
        try {
            // Check if employee exists
            $stmt = $this->employeeModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Employee not found');
                return;
            }
            
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['firstname', 'lastname', 'contact_number', 'email'])
                ->email('email');
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Set employee properties
            $this->employeeModel->employee_id = $id;
            $this->employeeModel->firstname = $data['firstname'];
            $this->employeeModel->lastname = $data['lastname'];
            $this->employeeModel->contact_number = $data['contact_number'];
            $this->employeeModel->email = $data['email'];
            
            // Update the employee
            if ($this->employeeModel->update()) {
                $employee = [
                    'employee_id' => $this->employeeModel->employee_id,
                    'firstname' => $this->employeeModel->firstname,
                    'lastname' => $this->employeeModel->lastname,
                    'contact_number' => $this->employeeModel->contact_number,
                    'email' => $this->employeeModel->email
                ];
                
                ResponseHandler::success('Employee updated successfully', $employee);
            } else {
                ResponseHandler::serverError('Failed to update employee');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error updating employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete an employee
     *
     * @param string $id Employee ID
     */
    protected function delete($id) {
        try {
            // Check if employee exists
            $stmt = $this->employeeModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Employee not found');
                return;
            }
            
            // Set ID and delete
            $this->employeeModel->employee_id = $id;
            
            if ($this->employeeModel->delete()) {
                ResponseHandler::success('Employee deleted successfully');
            } else {
                ResponseHandler::serverError('Failed to delete employee');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error deleting employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle subresource requests
     *
     * @param string $id Employee ID
     * @param string $subResource Subresource name
     * @param string $method HTTP method
     */
    protected function handleSubresource($id, $subResource, $method = 'GET') {
        // Check if employee exists
        $stmt = $this->employeeModel->readOne($id);
        if ($stmt->rowCount() === 0) {
            ResponseHandler::notFound('Employee not found');
            return;
        }
        
        switch ($subResource) {
            case 'accounts':
                $this->handleAccountsSubresource($id, $method);
                break;
                
            case 'banking':
                $this->handleBankingSubresource($id, $method);
                break;
                
            default:
                ResponseHandler::notFound('Subresource not found');
                break;
        }
    }
    
    /**
     * Handle accounts subresource
     *
     * @param string $employeeId Employee ID
     * @param string $method HTTP method
     */
    private function handleAccountsSubresource($employeeId, $method) {
        require_once __DIR__ . '/../models/Account.php';
        $accountModel = new Account($this->db);
        
        if ($method === 'GET') {
            // Get accounts for employee
            $stmt = $accountModel->readByEmployeeId($employeeId);
            $accounts = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Remove password from response
                unset($row['account_password']);
                $accounts[] = $row;
            }
            
            ResponseHandler::success('Accounts retrieved successfully', $accounts);
        } else if ($method === 'POST') {
            // Create new account for employee
            $data = $this->getJsonInput();
            
            // Validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['account_email', 'account_password', 'account_type'])
                ->email('account_email');
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Set account properties
            $accountModel->employee_id = $employeeId;
            $accountModel->account_email = $data['account_email'];
            $accountModel->account_password = $data['account_password'];
            $accountModel->account_type = $data['account_type'];
            $accountModel->account_status = $data['account_status'] ?? 'ACTIVE';
            
            // Create the account
            if ($accountModel->create()) {
                $account = [
                    'account_id' => $accountModel->account_id,
                    'employee_id' => $accountModel->employee_id,
                    'account_email' => $accountModel->account_email,
                    'account_type' => $accountModel->account_type,
                    'account_status' => $accountModel->account_status
                ];
                
                ResponseHandler::created('Account created successfully', $account);
            } else {
                ResponseHandler::serverError('Failed to create account');
            }
        } else {
            ResponseHandler::badRequest('Method not allowed for this subresource');
        }
    }
    
    /**
     * Handle banking subresource
     *
     * @param string $employeeId Employee ID
     * @param string $method HTTP method
     */
    private function handleBankingSubresource($employeeId, $method) {
        require_once __DIR__ . '/../models/Banking.php';
        $bankingModel = new Banking($this->db);
        
        if ($method === 'GET') {
            // Get banking details for employee
            $stmt = $bankingModel->readByEmployeeId($employeeId);
            $bankingDetails = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bankingDetails[] = $row;
            }
            
            ResponseHandler::success('Banking details retrieved successfully', $bankingDetails);
        } else if ($method === 'POST') {
            // Create new banking detail for employee
            $data = $this->getJsonInput();
            
            // Validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['preferred_bank', 'bank_account_number', 'bank_account_name']);
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Check if banking detail already exists
            if ($bankingModel->bankingDetailExists($employeeId, $data['bank_account_number'])) {
                ResponseHandler::badRequest('Banking detail with this account number already exists for this employee');
                return;
            }
            
            // Set banking properties
            $bankingModel->employee_id = $employeeId;
            $bankingModel->preferred_bank = $data['preferred_bank'];
            $bankingModel->bank_account_number = $data['bank_account_number'];
            $bankingModel->bank_account_name = $data['bank_account_name'];
            
            // Create the banking detail
            if ($bankingModel->create()) {
                $banking = [
                    'id' => $bankingModel->id,
                    'employee_id' => $bankingModel->employee_id,
                    'preferred_bank' => $bankingModel->preferred_bank,
                    'bank_account_number' => $bankingModel->bank_account_number,
                    'bank_account_name' => $bankingModel->bank_account_name
                ];
                
                ResponseHandler::created('Banking detail created successfully', $banking);
            } else {
                ResponseHandler::serverError('Failed to create banking detail');
            }
        } else {
            ResponseHandler::badRequest('Method not allowed for this subresource');
        }
    }
}