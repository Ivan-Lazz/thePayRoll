<?php
require_once __DIR__ . '/../utils/ResponseHandler.php';
require_once __DIR__ . '/../utils/InputValidator.php';
require_once __DIR__ . '/../utils/TokenManager.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

/**
 * AuthController - Handles authentication
 */
class AuthController {
    private $db;
    private $validator;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->validator = new InputValidator();
    }
    
    /**
     * Handle API request
     *
     * @param string $method HTTP method
     * @param string|null $action Action (login, logout, etc.)
     * @param string|null $subResource Sub-resource
     */
    public function handleRequest($method, $action = null, $subResource = null) {
        if ($method !== 'POST' && $method !== 'GET') {
            ResponseHandler::badRequest('Method not allowed for authentication');
            return;
        }
        
        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'refresh':
                $this->refreshToken();
                break;
            case 'check':
                $this->checkAuth();
                break;
            default:
                ResponseHandler::notFound('Auth action not found');
                break;
        }
    }
    
    /**
     * Login user
     */
    private function login() {
        // Create initial admin user if no users exist
        $this->ensureInitialUserExists();
        
        // Get input data
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST; // Support traditional form post
        }
        
        // Validate input
        $this->validator->setData($data);
        $this->validator->required(['username', 'password']);
        
        if (!$this->validator->isValid()) {
            ResponseHandler::badRequest('Invalid input', $this->validator->getErrors());
            return;
        }
        
        // Get sanitized data
        $data = $this->validator->getSanitizedData();
        $username = $data['username'];
        $password = $data['password'];
        
        // Check if user exists
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // User not found
            ResponseHandler::unauthorized('Invalid username or password');
            return;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            ResponseHandler::unauthorized('Your account is not active');
            return;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            ResponseHandler::unauthorized('Invalid username or password');
            return;
        }
        
        // Remove password from user data
        unset($user['password']);
        
        // Generate JWT token
        $tokenData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'exp' => time() + JWT_EXPIRY
        ];
        
        $token = TokenManager::generateToken($tokenData);
        
        // Generate CSRF token
        $csrfToken = TokenManager::generateCSRFToken();
        
        // Store user data in session
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        
        // Prepare response
        $response = [
            'token' => $token,
            'csrf_token' => $csrfToken,
            'user' => $user
        ];
        
        ResponseHandler::success('Login successful', $response);
    }
    
    /**
     * Logout user
     */
    private function logout() {
        // Clear session
        AuthMiddleware::clearSession();
        
        ResponseHandler::success('Logout successful');
    }

    /**
 * Check if any users exist and create an initial admin if none found
 * 
 * @return bool Whether an initial user was created
 */
    private function ensureInitialUserExists() {
        // Check if any users exist
        $query = "SELECT COUNT(*) as user_count FROM users";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no users exist, create initial admin account
        if ((int)$row['user_count'] === 0) {
            $initialUser = [
                'firstname' => 'Admin',
                'lastname' => 'User',
                'username' => 'admin',
                'password' => 'Admin@123', // This will be hashed during creation
                'email' => 'admin@example.com',
                'role' => 'admin',
                'status' => 'active'
            ];
            
            // Use User model to create the account
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User($this->db);
            
            $userModel->firstname = $initialUser['firstname'];
            $userModel->lastname = $initialUser['lastname'];
            $userModel->username = $initialUser['username'];
            $userModel->password = $initialUser['password'];
            $userModel->email = $initialUser['email'];
            $userModel->role = $initialUser['role'];
            $userModel->status = $initialUser['status'];
            
            // Create the user and log the action
            if ($userModel->create()) {
                // Log the creation of initial admin
                error_log("Initial admin user created successfully with username: admin");
                return true;
            } else {
                // Log the failure
                error_log("Failed to create initial admin user");
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Refresh JWT token
     */
    private function refreshToken() {
        // Check if user is authenticated
        if (!AuthMiddleware::isAuthenticated()) {
            ResponseHandler::unauthorized('Authentication required');
        }
        
        $user = AuthMiddleware::getCurrentUser();
        
        // Generate new JWT token
        $tokenData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        $token = TokenManager::generateToken($tokenData);
        
        // Generate new CSRF token
        $csrfToken = TokenManager::generateCSRFToken();
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Prepare response
        $response = [
            'token' => $token,
            'csrf_token' => $csrfToken,
            'expires_in' => JWT_EXPIRY
        ];
        
        ResponseHandler::success('Token refreshed', $response);
    }
    
    /**
     * Check authentication status
     */
    private function checkAuth() {
        if (AuthMiddleware::isAuthenticated()) {
            $user = AuthMiddleware::getCurrentUser();
            ResponseHandler::success('Authenticated', $user);
        } else {
            ResponseHandler::unauthorized('Not authenticated');
        }
    }
}