<?php
/**
 * CSRFMiddleware - Handles CSRF protection
 */
class CSRFMiddleware {
    // Define missing constant
    const CSRF_TOKEN_NAME = 'csrf_token';
    
    /**
     * Verify CSRF token on state-changing requests
     */
    public static function verifyToken() {
        // Only check on state-changing requests
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET' || $method === 'OPTIONS') {
            return;
        }
        
        // API requests with Bearer token are exempt from CSRF
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            return;
        }
        
        // Find the token from various sources
        $token = self::getCSRFToken();
        
        if (!$token || !TokenManager::validateCSRFToken($token)) {
            ResponseHandler::forbidden('CSRF token validation failed');
            exit;
        }
    }
    
    /**
     * Get the CSRF token from various sources
     *
     * @return string|null CSRF token or null if not found
     */
    private static function getCSRFToken() {
        // From POST/PUT data
        if (isset($_POST[self::CSRF_TOKEN_NAME])) {
            return $_POST[self::CSRF_TOKEN_NAME];
        }
        
        // From request headers
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        // From JSON request body
        $json = file_get_contents('php://input');
        if ($json) {
            $data = json_decode($json, true);
            if (isset($data[self::CSRF_TOKEN_NAME])) {
                return $data[self::CSRF_TOKEN_NAME];
            }
        }
        
        return null;
    }
    
    /**
     * Generate a new CSRF token and return it
     *
     * @return string New CSRF token
     */
    public static function generateToken() {
        return TokenManager::generateCSRFToken();
    }
}