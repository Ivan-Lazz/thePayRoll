<?php
require_once __DIR__ . '/../config/config.php';

/**
 * TokenManager - Manages JWT tokens for authentication
 */
class TokenManager {

    const CSRF_TOKEN_NAME = 'csrf_token';

    /**
     * Generate a JWT token
     *
     * @param array $payload Data to include in token
     * @return string JWT token
     */
    public static function generateToken($payload) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['exp'] = time() + JWT_EXPIRY;
        $payload['iat'] = time();
        
        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * Validate and decode a JWT token
     *
     * @param string $token JWT token
     * @return array|bool Payload if valid, false otherwise
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = self::base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }
        
        return $payload;
    }
    
    /**
     * Generate a CSRF token
     *
     * @return string CSRF token
     */
    public static function generateCSRFToken() {
        // Generate a random token
        $token = bin2hex(random_bytes(32));
        
        // Store in session
        $_SESSION[self::CSRF_TOKEN_NAME] = [
            'token' => $token,
            'expires' => time() + CSRF_TOKEN_EXPIRY
        ];
        
        return $token;
    }
    
    /**
     * Validate a CSRF token
     *
     * @param string $token CSRF token to validate
     * @return bool Whether token is valid
     */
    public static function validateCSRFToken($token) {
        // Check if token exists in session
        if (!isset($_SESSION[self::CSRF_TOKEN_NAME])) {
            return false;
        }
        
        // Get token data
        $tokenData = $_SESSION[self::CSRF_TOKEN_NAME];
        
        // Check if token matches
        if ($tokenData['token'] !== $token) {
            return false;
        }
        
        // Check if token has expired
        if ($tokenData['expires'] < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Base64Url encode
     *
     * @param string $data Data to encode
     * @return string Base64Url encoded string
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64Url decode
     *
     * @param string $data Data to decode
     * @return string Decoded data
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}