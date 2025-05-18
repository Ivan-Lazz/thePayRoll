<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Payslip.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Banking.php';
require_once __DIR__ . '/../services/PDFGenerator.php';

/**
 * PayslipController - Handles payslip-related API endpoints
 */
class PayslipController extends BaseController {
    private $payslipModel;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        parent::__construct($db);
        $this->payslipModel = new Payslip($db);
    }
    
    /**
     * Get all payslips with pagination
     */
    protected function getAll() {
        try {
            // Get pagination and search parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : DEFAULT_PAGE_SIZE;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            // Validate pagination parameters
            $page = max(1, $page);
            $perPage = min(max(1, $perPage), MAX_PAGE_SIZE);
            
            // Set pagination properties in the model
            $this->payslipModel->page = $page;
            $this->payslipModel->records_per_page = $perPage;
            
            // Get payslip data
            $stmt = $this->payslipModel->readPaginated($search, $status, $startDate, $endDate);
            $payslips = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Format employee name
                $row['employee_name'] = $row['firstname'] . ' ' . $row['lastname'];
                
                // Format bank details
                $row['bank_details'] = [
                    'preferred_bank' => $row['preferred_bank'],
                    'bank_account_number' => $row['bank_account_number'],
                    'bank_account_name' => $row['bank_account_name']
                ];
                
                // Remove duplicate fields
                unset($row['firstname'], $row['lastname'], $row['preferred_bank'], $row['bank_account_number'], $row['bank_account_name']);
                
                $payslips[] = $row;
            }
            
            // Get total count for pagination
            $totalRecords = $this->payslipModel->countAll($search, $status, $startDate, $endDate);
            
            // Return paginated response
            ResponseHandler::paginated($payslips, $page, $perPage, $totalRecords);
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving payslips: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single payslip by ID
     *
     * @param string $id Payslip ID
     */
    protected function getOne($id) {
        try {
            // Determine if ID is a payslip number or database ID
            $type = (strlen($id) === 9 && is_numeric($id)) ? 'no' : 'id';
            
            $stmt = $this->payslipModel->readOne($id, $type);
            
            if ($stmt->rowCount() > 0) {
                $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format employee name
                $payslip['employee_name'] = $payslip['firstname'] . ' ' . $payslip['lastname'];
                
                // Format bank details
                $payslip['bank_details'] = [
                    'preferred_bank' => $payslip['preferred_bank'],
                    'bank_account_number' => $payslip['bank_account_number'],
                    'bank_account_name' => $payslip['bank_account_name']
                ];
                
                // Remove duplicate fields
                unset($payslip['firstname'], $payslip['lastname'], $payslip['preferred_bank'], $payslip['bank_account_number'], $payslip['bank_account_name']);
                
                ResponseHandler::success('Payslip retrieved successfully', $payslip);
            } else {
                ResponseHandler::notFound('Payslip not found');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving payslip: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new payslip
     */
    protected function create() {
        try {
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['employee_id', 'bank_account_id', 'salary', 'bonus', 'person_in_charge', 'cutoff_date', 'payment_date', 'payment_status'])
                ->numeric('salary')
                ->numeric('bonus')
                ->date('cutoff_date')
                ->date('payment_date');
            
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
            
            // Check if banking detail exists
            $bankingModel = new Banking($this->db);
            $bankingStmt = $bankingModel->readOne($data['bank_account_id']);
            if ($bankingStmt->rowCount() === 0) {
                ResponseHandler::badRequest('Banking detail not found');
                return;
            }
            
            // Set payslip properties
            $this->payslipModel->employee_id = $data['employee_id'];
            $this->payslipModel->bank_account_id = $data['bank_account_id'];
            $this->payslipModel->salary = $data['salary'];
            $this->payslipModel->bonus = $data['bonus'];
            $this->payslipModel->total_salary = $data['salary'] + $data['bonus'];
            $this->payslipModel->person_in_charge = $data['person_in_charge'];
            $this->payslipModel->cutoff_date = $data['cutoff_date'];
            $this->payslipModel->payment_date = $data['payment_date'];
            $this->payslipModel->payment_status = $data['payment_status'];
            
            // Generate payslip number
            $this->payslipModel->payslip_no = $this->payslipModel->generatePayslipNo();
            
            // Create the payslip
            if ($this->payslipModel->create()) {
                // Get employee and banking details for PDF generation
                $employeeStmt = $employeeModel->readOne($data['employee_id']);
                $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);
                
                $banking = $bankingStmt->fetch(PDO::FETCH_ASSOC);
                
                // Prepare data for PDF generation
                $pdfData = [
                    'payslip_no' => $this->payslipModel->payslip_no,
                    'employee_id' => $this->payslipModel->employee_id,
                    'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                    'bank_details' => [
                        'preferred_bank' => $banking['preferred_bank'],
                        'bank_account_number' => $banking['bank_account_number'],
                        'bank_account_name' => $banking['bank_account_name']
                    ],
                    'salary' => $this->payslipModel->salary,
                    'bonus' => $this->payslipModel->bonus,
                    'total_salary' => $this->payslipModel->total_salary,
                    'person_in_charge' => $this->payslipModel->person_in_charge,
                    'cutoff_date' => $this->payslipModel->cutoff_date,
                    'payment_date' => $this->payslipModel->payment_date,
                    'payment_status' => $this->payslipModel->payment_status
                ];
                
                // Generate PDFs
                $pdfGenerator = new PDFGenerator();
                $agentPdfPath = $pdfGenerator->generateAgentPayslip($pdfData);
                $adminPdfPath = $pdfGenerator->generateAdminPayslip($pdfData);
                
                // Update payslip with PDF paths
                $this->payslipModel->id = $this->payslipModel->id;
                $this->payslipModel->agent_pdf_path = $agentPdfPath['path'];
                $this->payslipModel->admin_pdf_path = $adminPdfPath['path'];
                $this->payslipModel->updatePDFPaths();
                
                // Prepare response data
                $payslip = [
                    'id' => $this->payslipModel->id,
                    'payslip_no' => $this->payslipModel->payslip_no,
                    'employee_id' => $this->payslipModel->employee_id,
                    'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                    'bank_account_id' => $this->payslipModel->bank_account_id,
                    'bank_details' => [
                        'preferred_bank' => $banking['preferred_bank'],
                        'bank_account_number' => $banking['bank_account_number'],
                        'bank_account_name' => $banking['bank_account_name']
                    ],
                    'salary' => $this->payslipModel->salary,
                    'bonus' => $this->payslipModel->bonus,
                    'total_salary' => $this->payslipModel->total_salary,
                    'person_in_charge' => $this->payslipModel->person_in_charge,
                    'cutoff_date' => $this->payslipModel->cutoff_date,
                    'payment_date' => $this->payslipModel->payment_date,
                    'payment_status' => $this->payslipModel->payment_status,
                    'agent_pdf_path' => $this->payslipModel->agent_pdf_path,
                    'admin_pdf_path' => $this->payslipModel->admin_pdf_path
                ];
                
                ResponseHandler::created('Payslip created successfully', $payslip);
            } else {
                ResponseHandler::serverError('Failed to create payslip');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error creating payslip: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing payslip
     *
     * @param int $id Payslip ID
     */
    protected function update($id) {
        try {
            // Check if payslip exists
            $stmt = $this->payslipModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Payslip not found');
                return;
            }
            
            $existingPayslip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['employee_id', 'bank_account_id', 'salary', 'bonus', 'person_in_charge', 'cutoff_date', 'payment_date', 'payment_status'])
                ->numeric('salary')
                ->numeric('bonus')
                ->date('cutoff_date')
                ->date('payment_date');
            
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
            
            // Check if banking detail exists
            $bankingModel = new Banking($this->db);
            $bankingStmt = $bankingModel->readOne($data['bank_account_id']);
            if ($bankingStmt->rowCount() === 0) {
                ResponseHandler::badRequest('Banking detail not found');
                return;
            }
            
            // Set payslip properties
            $this->payslipModel->id = $id;
            $this->payslipModel->employee_id = $data['employee_id'];
            $this->payslipModel->bank_account_id = $data['bank_account_id'];
            $this->payslipModel->salary = $data['salary'];
            $this->payslipModel->bonus = $data['bonus'];
            $this->payslipModel->total_salary = $data['salary'] + $data['bonus'];
            $this->payslipModel->person_in_charge = $data['person_in_charge'];
            $this->payslipModel->cutoff_date = $data['cutoff_date'];
            $this->payslipModel->payment_date = $data['payment_date'];
            $this->payslipModel->payment_status = $data['payment_status'];
            
            // Update the payslip
            if ($this->payslipModel->update()) {
                // Get employee and banking details for PDF generation
                $employeeStmt = $employeeModel->readOne($data['employee_id']);
                $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);
                
                $banking = $bankingStmt->fetch(PDO::FETCH_ASSOC);
                
                // Prepare data for PDF generation
                $pdfData = [
                    'payslip_no' => $existingPayslip['payslip_no'],
                    'employee_id' => $this->payslipModel->employee_id,
                    'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                    'bank_details' => [
                        'preferred_bank' => $banking['preferred_bank'],
                        'bank_account_number' => $banking['bank_account_number'],
                        'bank_account_name' => $banking['bank_account_name']
                    ],
                    'salary' => $this->payslipModel->salary,
                    'bonus' => $this->payslipModel->bonus,
                    'total_salary' => $this->payslipModel->total_salary,
                    'person_in_charge' => $this->payslipModel->person_in_charge,
                    'cutoff_date' => $this->payslipModel->cutoff_date,
                    'payment_date' => $this->payslipModel->payment_date,
                    'payment_status' => $this->payslipModel->payment_status
                ];
                
                // Generate PDFs
                $pdfGenerator = new PDFGenerator();
                $agentPdfPath = $pdfGenerator->generateAgentPayslip($pdfData);
                $adminPdfPath = $pdfGenerator->generateAdminPayslip($pdfData);
                
                // Update payslip with PDF paths
                $this->payslipModel->id = $id;
                $this->payslipModel->agent_pdf_path = $agentPdfPath['path'];
                $this->payslipModel->admin_pdf_path = $adminPdfPath['path'];
                $this->payslipModel->updatePDFPaths();
                
                // Prepare response data
                $payslip = [
                    'id' => $this->payslipModel->id,
                    'payslip_no' => $existingPayslip['payslip_no'],
                    'employee_id' => $this->payslipModel->employee_id,
                    'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                    'bank_account_id' => $this->payslipModel->bank_account_id,
                    'bank_details' => [
                        'preferred_bank' => $banking['preferred_bank'],
                        'bank_account_number' => $banking['bank_account_number'],
                        'bank_account_name' => $banking['bank_account_name']
                    ],
                    'salary' => $this->payslipModel->salary,
                    'bonus' => $this->payslipModel->bonus,
                    'total_salary' => $this->payslipModel->total_salary,
                    'person_in_charge' => $this->payslipModel->person_in_charge,
                    'cutoff_date' => $this->payslipModel->cutoff_date,
                    'payment_date' => $this->payslipModel->payment_date,
                    'payment_status' => $this->payslipModel->payment_status,
                    'agent_pdf_path' => $this->payslipModel->agent_pdf_path,
                    'admin_pdf_path' => $this->payslipModel->admin_pdf_path
                ];
                
                ResponseHandler::success('Payslip updated successfully', $payslip);
            } else {
                ResponseHandler::serverError('Failed to update payslip');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error updating payslip: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a payslip
     *
     * @param int $id Payslip ID
     */
    protected function delete($id) {
        try {
            // Check if payslip exists
            $stmt = $this->payslipModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Payslip not found');
                return;
            }
            
            // Set ID and delete
            $this->payslipModel->id = $id;
            
            if ($this->payslipModel->delete()) {
                ResponseHandler::success('Payslip deleted successfully');
            } else {
                ResponseHandler::serverError('Failed to delete payslip');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error deleting payslip: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle subresource requests
     *
     * @param string $id ID
     * @param string $subResource Subresource name
     * @param string $method HTTP method
     */
    protected function handleSubresource($id, $subResource, $method = 'GET') {
        if ($subResource === 'generate-pdf' && $method === 'POST') {
            $this->regeneratePDFs($id);
        } else if ($subResource === 'employee' && $method === 'GET') {
            $this->getPayslipsByEmployee($id);
        } else if ($subResource === 'statuses' && $method === 'GET') {
            $statuses = $this->payslipModel->getPaymentStatuses();
            ResponseHandler::success('Payment statuses retrieved successfully', $statuses);
        } else {
            ResponseHandler::notFound('Subresource not found');
        }
    }
    
    /**
     * Regenerate PDFs for a payslip
     *
     * @param int $id Payslip ID
     */
    private function regeneratePDFs($id) {
        try {
            // Check if payslip exists
            $stmt = $this->payslipModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('Payslip not found');
                return;
            }
            
            $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get employee and banking details
            $employeeModel = new Employee($this->db);
            $employeeStmt = $employeeModel->readOne($payslip['employee_id']);
            $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);
            
            // Prepare data for PDF generation
            $pdfData = [
                'payslip_no' => $payslip['payslip_no'],
                'employee_id' => $payslip['employee_id'],
                'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                'bank_details' => [
                    'preferred_bank' => $payslip['preferred_bank'],
                    'bank_account_number' => $payslip['bank_account_number'],
                    'bank_account_name' => $payslip['bank_account_name']
                ],
                'salary' => $payslip['salary'],
                'bonus' => $payslip['bonus'],
                'total_salary' => $payslip['total_salary'],
                'person_in_charge' => $payslip['person_in_charge'],
                'cutoff_date' => $payslip['cutoff_date'],
                'payment_date' => $payslip['payment_date'],
                'payment_status' => $payslip['payment_status']
            ];
            
            // Generate PDFs
            $pdfGenerator = new PDFGenerator();
            $agentPdfPath = $pdfGenerator->generateAgentPayslip($pdfData);
            $adminPdfPath = $pdfGenerator->generateAdminPayslip($pdfData);
            
            // Update payslip with PDF paths
            $this->payslipModel->id = $id;
            $this->payslipModel->agent_pdf_path = $agentPdfPath['path'];
            $this->payslipModel->admin_pdf_path = $adminPdfPath['path'];
            
            if ($this->payslipModel->updatePDFPaths()) {
                ResponseHandler::success('PDFs regenerated successfully', [
                    'agent_pdf_path' => $this->payslipModel->agent_pdf_path,
                    'admin_pdf_path' => $this->payslipModel->admin_pdf_path
                ]);
            } else {
                ResponseHandler::serverError('Failed to update PDF paths');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error regenerating PDFs: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payslips by employee ID
     *
     * @param string $employeeId Employee ID
     */
    private function getPayslipsByEmployee($employeeId) {
        try {
            // Check if employee exists
            $employeeModel = new Employee($this->db);
            if (!$employeeModel->employeeExists($employeeId)) {
                ResponseHandler::notFound('Employee not found');
                return;
            }
            
            // Get payslips for employee
            $stmt = $this->payslipModel->readByEmployeeId($employeeId);
            $payslips = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Format employee name
                $row['employee_name'] = $row['firstname'] . ' ' . $row['lastname'];
                
                // Format bank details
                $row['bank_details'] = [
                    'preferred_bank' => $row['preferred_bank'],
                    'bank_account_number' => $row['bank_account_number'],
                    'bank_account_name' => $row['bank_account_name']
                ];
                
                // Remove duplicate fields
                unset($row['firstname'], $row['lastname'], $row['preferred_bank'], $row['bank_account_number'], $row['bank_account_name']);
                
                $payslips[] = $row;
            }
            
            ResponseHandler::success('Payslips retrieved successfully', $payslips);
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving payslips: ' . $e->getMessage());
        }
    }
}