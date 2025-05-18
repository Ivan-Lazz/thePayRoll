<?php
require_once __DIR__ . '/../utils/ResponseHandler.php';
require_once __DIR__ . '/../utils/InputValidator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

/**
 * BaseController - Parent class for all controllers
 */
abstract class BaseController {
    protected $db;
    protected $validator;
    protected $currentUser;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->validator = new InputValidator();
        $this->currentUser = AuthMiddleware::getCurrentUser();
    }
    
    /**
     * Handle API request
     * 
     * @param string $method HTTP method
     * @param string|null $id Resource identifier
     * @param string|null $subResource Sub-resource name
     */
    public function handleRequest($method, $id = null, $subResource = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    if ($subResource) {
                        $this->handleSubresource($id, $subResource);
                    } else {
                        $this->getOne($id);
                    }
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                if ($id && $subResource) {
                    $this->handleSubresource($id, $subResource, 'POST');
                } else {
                    $this->create();
                }
                break;
                
            case 'PUT':
                if ($id) {
                    $this->update($id);
                } else {
                    ResponseHandler::badRequest('Resource ID is required for update');
                }
                break;
                
            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                } else {
                    ResponseHandler::badRequest('Resource ID is required for deletion');
                }
                break;
                
            default:
                ResponseHandler::badRequest('Method not supported');
                break;
        }
    }
    
    /**
     * Get all resources with pagination
     */
    protected function getAll() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : DEFAULT_PAGE_SIZE;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Validate pagination parameters
        $page = max(1, $page); // Ensure page is at least 1
        $perPage = min(max(1, $perPage), MAX_PAGE_SIZE); // Ensure perPage is between 1 and MAX_PAGE_SIZE
        
        // This method should be implemented by child classes
        ResponseHandler::notFound('Method not implemented');
    }
    
    /**
     * Get one resource by ID
     *
     * @param string $id Resource ID
     */
    protected function getOne($id) {
        // This method should be implemented by child classes
        ResponseHandler::notFound('Method not implemented');
    }
    
    /**
     * Create a new resource
     */
    protected function create() {
        // This method should be implemented by child classes
        ResponseHandler::notFound('Method not implemented');
    }
    
    /**
     * Update an existing resource
     *
     * @param string $id Resource ID
     */
    protected function update($id) {
        // This method should be implemented by child classes
        ResponseHandler::notFound('Method not implemented');
    }
    
    /**
     * Delete a resource
     *
     * @param string $id Resource ID
     */
    protected function delete($id) {
        // This method should be implemented by child classes
        ResponseHandler::notFound('Method not implemented');
    }
    
    /**
     * Handle subresource requests
     *
     * @param string $id Parent resource ID
     * @param string $subResource Subresource name
     * @param string $method HTTP method for subresource
     */
    protected function handleSubresource($id, $subResource, $method = 'GET') {
        // This method should be implemented by child classes that need subresources
        ResponseHandler::notFound('Subresource not found');
    }
    
    /**
     * Get and validate JSON input data
     *
     * @return array Parsed JSON data
     */
    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            ResponseHandler::badRequest('Invalid JSON data: ' . json_last_error_msg());
        }
        
        return $data ?? [];
    }
    
    /**
     * Check if resource exists
     *
     * @param string $table Table name
     * @param string $idField ID field name
     * @param string $id Resource ID
     * @return bool Whether resource exists
     */
    protected function resourceExists($table, $idField, $id) {
        $query = "SELECT 1 FROM $table WHERE $idField = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}