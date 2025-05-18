<?php

class CORSMiddleware {
    public static function handleCORS() {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Check if the origin is in our allowed list
        if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: $origin");
        } elseif (APP_ENV === 'development') {
            // In development, allow any origin (but still log it)
            error_log("Warning: Unregistered origin accessing API: $origin");
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // In production, only allow registered origins
            header("Access-Control-Allow-Origin: " . FRONTEND_URL);
        }

        // Allow credentials
        header("Access-Control-Allow-Credentials: true");
        
        // Cache preflight request for 24 hours
        header("Access-Control-Max-Age: 86400");
    }
} 