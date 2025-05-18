<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

/**
 * UserController - Handles user-related API endpoints
 */
class UserController extends BaseController {
    private $userModel;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        parent::__construct($db);
        $this->userModel = new User($db);
    }
    
    /**
     * Get all users with pagination
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
            $this->userModel->page = $page;
            $this->userModel->records_per_page = $perPage;
            
            // Get user data
            $stmt = $this->userModel->readPaginated($search);
            $users = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Remove password from response
                unset($row['password']);
                $users[] = $row;
            }
            
            // Get total count for pagination
            $totalRecords = $this->userModel->countAll($search);
            
            // Return paginated response
            ResponseHandler::paginated($users, $page, $perPage, $totalRecords);
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving users: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single user by ID
     *
     * @param int $id User ID
     */
    protected function getOne($id) {
        try {
            $stmt = $this->userModel->readOne($id);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Remove password from response
                unset($user['password']);
                
                ResponseHandler::success('User retrieved successfully', $user);
            } else {
                ResponseHandler::notFound('User not found');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error retrieving user: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new user
     */
    protected function create() {
        try {
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['firstname', 'lastname', 'username', 'password'])
                ->minLength('username', 3)
                ->minLength('password', 6)
                ->email('email');
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Set user properties
            $this->userModel->firstname = $data['firstname'];
            $this->userModel->lastname = $data['lastname'];
            $this->userModel->username = $data['username'];
            $this->userModel->password = $data['password'];
            $this->userModel->email = $data['email'] ?? null;
            $this->userModel->role = $data['role'] ?? 'user';
            $this->userModel->status = $data['status'] ?? 'active';
            
            // Create the user
            if ($this->userModel->create()) {
                $user = [
                    'id' => $this->userModel->id,
                    'firstname' => $this->userModel->firstname,
                    'lastname' => $this->userModel->lastname,
                    'username' => $this->userModel->username,
                    'email' => $this->userModel->email,
                    'role' => $this->userModel->role,
                    'status' => $this->userModel->status
                ];
                
                ResponseHandler::created('User created successfully', $user);
            } else {
                ResponseHandler::serverError('Failed to create user');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error creating user: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing user
     *
     * @param int $id User ID
     */
    protected function update($id) {
        try {
            // Check if user exists
            $stmt = $this->userModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('User not found');
                return;
            }
            
            // Get posted data
            $data = $this->getJsonInput();
            
            // Set validator and validate input
            $this->validator->setData($data);
            $this->validator
                ->required(['firstname', 'lastname', 'username'])
                ->minLength('username', 3)
                ->email('email');
            
            // Validate password only if provided
            if (!empty($data['password'])) {
                $this->validator->minLength('password', 6);
            }
            
            if (!$this->validator->isValid()) {
                ResponseHandler::badRequest('Invalid input data', $this->validator->getErrors());
                return;
            }
            
            // Set user properties
            $this->userModel->id = $id;
            $this->userModel->firstname = $data['firstname'];
            $this->userModel->lastname = $data['lastname'];
            $this->userModel->username = $data['username'];
            $this->userModel->email = $data['email'] ?? null;
            $this->userModel->role = $data['role'] ?? 'user';
            $this->userModel->status = $data['status'] ?? 'active';
            
            // Set password only if provided
            if (!empty($data['password'])) {
                $this->userModel->password = $data['password'];
            }
            
            // Update the user
            if ($this->userModel->update()) {
                $user = [
                    'id' => $this->userModel->id,
                    'firstname' => $this->userModel->firstname,
                    'lastname' => $this->userModel->lastname,
                    'username' => $this->userModel->username,
                    'email' => $this->userModel->email,
                    'role' => $this->userModel->role,
                    'status' => $this->userModel->status
                ];
                
                ResponseHandler::success('User updated successfully', $user);
            } else {
                ResponseHandler::serverError('Failed to update user');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error updating user: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a user
     *
     * @param int $id User ID
     */
    protected function delete($id) {
        try {
            // Check if user exists
            $stmt = $this->userModel->readOne($id);
            if ($stmt->rowCount() === 0) {
                ResponseHandler::notFound('User not found');
                return;
            }
            
            // Check if trying to delete the only admin
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user['role'] === 'admin') {
                // Count admins
                $query = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ((int)$row['admin_count'] <= 1) {
                    ResponseHandler::badRequest('Cannot delete the only admin user');
                    return;
                }
            }
            
            // Set ID and delete
            $this->userModel->id = $id;
            
            if ($this->userModel->delete()) {
                ResponseHandler::success('User deleted successfully');
            } else {
                ResponseHandler::serverError('Failed to delete user');
            }
        } catch (Exception $e) {
            ResponseHandler::serverError('Error deleting user: ' . $e->getMessage());
        }
    }
}