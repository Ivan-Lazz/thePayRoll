<?php
/**
 * InstallController - Handles database installation
 */
class InstallController {
    private $db;
    
    /**
     * Constructor
     *
     * @param Database $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Handle API request
     *
     * @param string $method HTTP method
     * @param string|null $id Resource identifier
     * @param string|null $subResource Sub-resource name
     */
    public function handleRequest($method, $id = null, $subResource = null) {
        // Only allow POST method for installation
        if ($method !== 'POST') {
            ResponseHandler::badRequest('Only POST method is allowed for installation');
            return;
        }
        
        $this->install();
    }
    
    /**
     * Install the database
     */
    public function install() {
        try {
            // Initialize database (creates database if it doesn't exist)
            $this->db->initializeDatabase();
            
            // Create required directories
            $this->createDirectories();
            
            ResponseHandler::success('Installation completed successfully');
        } catch (Exception $e) {
            ResponseHandler::serverError('Installation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create required directories
     */
    private function createDirectories() {
        // Define directories
        $directories = [
            PDF_PATH,
            AGENT_PDF_PATH,
            ADMIN_PDF_PATH
        ];
        
        // Create directories if they don't exist
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create directory: $dir");
                }
            } else if (!is_writable($dir)) {
                throw new Exception("Directory is not writable: $dir");
            }
        }
        
        // Create .htaccess to protect directories
        $htaccess = "Order Deny,Allow\nDeny from all\n";
        file_put_contents(PDF_PATH . '/.htaccess', $htaccess);
    }
}