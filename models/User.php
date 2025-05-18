<?php
/**
 * User Model - Handles database operations for users
 */
class User {
    private $conn;
    private $table_name = "users";
    
    // User properties
    public $id;
    public $firstname;
    public $lastname;
    public $username;
    public $password;
    public $email;
    public $role;
    public $status;
    public $created_at;
    public $updated_at;
    
    // Pagination properties
    public $page;
    public $records_per_page;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all users with pagination
     *
     * @param string $search Search term (optional)
     * @return PDOStatement Query result
     */
    public function readPaginated($search = '') {
        $offset = ($this->page - 1) * $this->records_per_page;
        
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE firstname LIKE :search 
                           OR lastname LIKE :search 
                           OR username LIKE :search
                           OR email LIKE :search";
        }
        
        $query = "SELECT id, firstname, lastname, username, email, role, status, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 {$whereClause} 
                 ORDER BY id ASC 
                 LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $this->records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Count total users
     *
     * @param string $search Search term (optional)
     * @return int Total number of users
     */
    public function countAll($search = '') {
        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE firstname LIKE :search 
                           OR lastname LIKE :search 
                           OR username LIKE :search
                           OR email LIKE :search";
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " {$whereClause}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }
    
    /**
     * Get single user by ID
     *
     * @param int $id User ID
     * @return PDOStatement Query result
     */
    public function readOne($id) {
        $query = "SELECT id, firstname, lastname, username, email, role, status, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 WHERE id = :id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get user by username
     *
     * @param string $username Username
     * @return PDOStatement Query result
     */
    public function readByUsername($username) {
        $query = "SELECT id, firstname, lastname, username, password, email, role, status, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 WHERE username = :username 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Create new user
     *
     * @return bool Whether operation was successful
     */
    public function create() {
        // Check if username already exists
        if ($this->usernameExists()) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                (firstname, lastname, username, password, email, role, status)
                VALUES
                (:firstname, :lastname, :username, :password, :email, :role, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role ?? 'user'));
        $this->status = htmlspecialchars(strip_tags($this->status ?? 'active'));
        
        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Update user
     *
     * @return bool Whether operation was successful
     */
    public function update() {
        // Check if trying to update username and if it exists
        if ($this->usernameExistsForUpdate()) {
            return false;
        }
        
        // Determine if password needs to be updated
        $passwordSet = !empty($this->password);
        $passwordClause = $passwordSet ? ", password = :password" : "";
        
        $query = "UPDATE " . $this->table_name . "
                SET
                    firstname = :firstname,
                    lastname = :lastname,
                    username = :username,
                    email = :email,
                    role = :role,
                    status = :status" . 
                    $passwordClause . "
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        // Bind password if it's being updated
        if ($passwordSet) {
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $stmt->bindParam(':password', $password_hash);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Delete user
     *
     * @return bool Whether operation was successful
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Check if username exists
     *
     * @return bool Whether username exists
     */
    private function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(':username', $this->username);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check if username exists for update
     *
     * @return bool Whether username exists for update
     */
    private function usernameExistsForUpdate() {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE username = :username AND id != :id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':id', $this->id);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}