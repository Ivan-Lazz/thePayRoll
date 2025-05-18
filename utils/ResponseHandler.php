<?php
/**
 * ResponseHandler - Helper class to standardize API responses
 */
class ResponseHandler {
    /**
     * Send a JSON response with appropriate status code
     *
     * @param int $statusCode HTTP status code
     * @param bool $success Whether the request was successful
     * @param string $message Human-readable message
     * @param array|null $data Optional data to include in response
     * @param array $additionalFields Optional additional fields to include
     */
    public static function json($statusCode, $success, $message, $data = null, $additionalFields = []) {
        http_response_code($statusCode);
        
        $response = [
            'status_code' => $statusCode,
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($additionalFields)) {
            $response = array_merge($response, $additionalFields);
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send a successful response
     */
    public static function success($message, $data = null, $additionalFields = []) {
        self::json(200, true, $message, $data, $additionalFields);
    }
    
    /**
     * Send a created response (201)
     */
    public static function created($message, $data = null, $additionalFields = []) {
        self::json(201, true, $message, $data, $additionalFields);
    }
    
    /**
     * Send a not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::json(404, false, $message);
    }
    
    /**
     * Send a bad request response
     */
    public static function badRequest($message, $errors = null) {
        $additional = $errors ? ['errors' => $errors] : [];
        self::json(400, false, $message, null, $additional);
    }
    
    /**
     * Send an unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::json(401, false, $message);
    }
    
    /**
     * Send a forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        self::json(403, false, $message);
    }
    
    /**
     * Send a server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::json(500, false, $message);
    }
    
    /**
     * Send paginated results
     */
    public static function paginated($data, $page, $perPage, $totalRecords) {
        $totalPages = ceil($totalRecords / $perPage);
        
        self::success('Data retrieved successfully', $data, [
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_records' => (int)$totalRecords,
                'total_pages' => (int)$totalPages
            ]
        ]);
    }
}