<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Banking.php';
require_once __DIR__ . '/../models/Employee.php';

/**
 * BankingController - Handles banking details-related API endpoints
 */
class BankingController extends BaseController {
    private $bankingModel;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        parent::__construct($db);
        $this->bankingModel = new Banking($db);
    }
    
    /**
     * Get all banking details with pagination
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
            $this->bankingModel->page = $page;
            $this->bankingModel->records_per_page = $perPage;
            
            // Get banking data
            $stmt = $this->bankingModel->readPaginated($search);
            $bankingDetails = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bankingDetails[] = $row;
            }
            
            // Get total count for pagination
            $totalRecords = $this->bankingModel->countAll($search);
            
            // Return paginated response
            ResponseHandler::paginated($bankingDetails, $page, $perPage, $totalRecords);
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving banking details: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single banking detail by ID
     *
     * @param int $id Banking detail ID
     */
    protected function getOne($id) {
        try {
            $stmt = $this->bankingModel->readOne($id);
            
            if ($stmt->rowCount() > 0) {
                $bankingDetail = $stmt->fetch(PDO::FETCH_ASSOC);
                ResponseHandler::success('Banking detail retrieved successfully', $bankingDetail);
            } else {
                ResponseHandler::notFound('Banking detail not found');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving banking detail: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new banking detail
     */
    protected function create() {
        try {
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['employee_id', 'preferred_bank', 'bank_account_number', 'bank_account_name']);
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Check if employee exists
            $employeeModel = new Employee($this->db);
            if (!$employeeModel->employeeExists($data['employee_id'])) {
                ResponseHandler::badRequest('Employee not found');
                return;
            }
            
            // Check if banking detail already exists
            if ($this->bankingModel->bankingDetailExists($data['employee_id'], $data['bank_account_number'])) {
                ResponseHandler::badRequest('Banking detail with this account number already exists for this employee');
                return;
            }
            
            // Set banking properties
            $this->bankingModel->employee_id = $data['employee_id'];
            $this->bankingModel->preferred_bank = $data['preferred_bank'];
            $this->bankingModel->bank_account_number = $data['bank_account_number'];
            $this->bankingModel->bank_account_name = $data['bank_account_name'];
            
            // Create the banking detail
            if ($this->bankingModel->create()) {
                $banking = [
                    'id' => $this->bankingModel->id,
                    'employee_id' => $this->bankingModel->employee_id,
                    'preferred_bank' => $this->bankingModel->preferred_bank,
                    'bank_account_number' => $this->bankingModel->bank_account_number,
                    'bank_account_name' => $this->bankingModel->bank_account_name
                ];
                
                ResponseHandler::created('Banking detail created successfully', $banking);
            } else {
                ResponseHandler::serverError('Failed to create banking detail');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error creating banking detail: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing banking detail
     *
     * @param int $id Banking detail ID
     */
    protected function update($id) {
        try {
            // Check if banking detail exists
            $stmt = $this->bankingModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Banking detail not found');
                return;
            }
            
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['employee_id', 'preferred_bank', 'bank_account_number', 'bank_account_name']);
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Check if employee exists
            $employeeModel = new Employee($this->db);
            if (!$employeeModel->employeeExists($data['employee_id'])) {
                ResponseHandler::badRequest('Employee not found');
                return;
            }
            
            // Set banking properties
            $this->bankingModel->id = $id;
            $this->bankingModel->employee_id = $data['employee_id'];
            $this->bankingModel->preferred_bank = $data['preferred_bank'];
            $this->bankingModel->bank_account_number = $data['bank_account_number'];
            $this->bankingModel->bank_account_name = $data['bank_account_name'];
            
            // Update the banking detail
            if ($this->bankingModel->update()) {
                $banking = [
                    'id' => $this->bankingModel->id,
                    'employee_id' => $this->bankingModel->employee_id,
                    'preferred_bank' => $this->bankingModel->preferred_bank,
                    'bank_account_number' => $this->bankingModel->bank_account_number,
                    'bank_account_name' => $this->bankingModel->bank_account_name
                ];
                
                ResponseHandler::success('Banking detail updated successfully', $banking);
            } else {
                ResponseHandler::serverError('Failed to update banking detail');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error updating banking detail: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a banking detail
     *
     * @param int $id Banking detail ID
     */
    protected function delete($id) {
        try {
            // Check if banking detail exists
            $stmt = $this->bankingModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Banking detail not found');
                return;
            }
            
            // Set ID and delete
            $this->bankingModel->id = $id;
            
            if ($this->bankingModel->delete()) {
                ResponseHandler::success('Banking detail deleted successfully');
            } else {
                ResponseHandler::serverError('Failed to delete banking detail');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error deleting banking detail: ' . $e->getMessage());
        }
    }
    
    /**
     * Get banking details by employee ID
     *
     * @param string $employeeId Employee ID
     */
    protected function handleSubresource($id, $subResource, $method = 'GET') {
        if ($subResource === 'employee' && $method === 'GET') {
            try {
                // Check if employee exists
                $employeeModel = new Employee($this->db);
                if (!$employeeModel->employeeExists($id)) {
                    ResponseHandler::notFound('Employee not found');
                    return;
                }
                
                // Get banking details for employee
                $stmt = $this->bankingModel->readByEmployeeId($id);
                $bankingDetails = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $bankingDetails[] = $row;
                }
                
                ResponseHandler::success('Banking details retrieved successfully', $bankingDetails);
            } catch (Exception $e) {
                ResponseHandler::serverError('Error retrieving banking details: ' . $e->getMessage());
            }
        } else {
            ResponseHandler::notFound('Subresource not found');
        }
    }
}
