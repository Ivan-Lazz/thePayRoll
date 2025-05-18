<?php
/**
 * InputValidator - Handles input validation and sanitization
 */
class InputValidator {
    private $errors = [];
    private $data = [];
    private $rawData = [];
    
    /**
     * Constructor that can accept input data
     *
     * @param array $data Data to validate
     */
    public function __construct($data = []) {
        $this->rawData = $data;
        $this->data = $data;
    }
    
    /**
     * Set data to validate
     *
     * @param array $data Data to validate
     * @return $this For method chaining
     */
    public function setData($data) {
        $this->rawData = $data;
        $this->data = $data;
        return $this;
    }
    
    /**
     * Get sanitized data
     *
     * @return array Sanitized data
     */
    public function getSanitizedData() {
        return $this->data;
    }
    
    /**
     * Get validation errors
     *
     * @return array Validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     *
     * @return bool Whether validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
    
    /**
     * Sanitize a string
     *
     * @param string $field Field name
     * @param string $default Default value if field is empty
     * @return $this For method chaining
     */
    public function sanitizeString($field, $default = '') {
        if (isset($this->data[$field])) {
            $this->data[$field] = htmlspecialchars(strip_tags(trim($this->data[$field])));
        } else {
            $this->data[$field] = $default;
        }
        return $this;
    }
    
    /**
     * Sanitize an email
     *
     * @param string $field Field name
     * @param string $default Default value if field is empty
     * @return $this For method chaining
     */
    public function sanitizeEmail($field, $default = '') {
        if (isset($this->data[$field])) {
            $this->data[$field] = filter_var(trim($this->data[$field]), FILTER_SANITIZE_EMAIL);
        } else {
            $this->data[$field] = $default;
        }
        return $this;
    }
    
    /**
     * Sanitize an integer
     *
     * @param string $field Field name
     * @param int $default Default value if field is empty
     * @return $this For method chaining
     */
    public function sanitizeInt($field, $default = 0) {
        if (isset($this->data[$field])) {
            $this->data[$field] = filter_var($this->data[$field], FILTER_SANITIZE_NUMBER_INT);
        } else {
            $this->data[$field] = $default;
        }
        return $this;
    }
    
    /**
     * Sanitize a float
     *
     * @param string $field Field name
     * @param float $default Default value if field is empty
     * @return $this For method chaining
     */
    public function sanitizeFloat($field, $default = 0.0) {
        if (isset($this->data[$field])) {
            $this->data[$field] = filter_var($this->data[$field], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        } else {
            $this->data[$field] = $default;
        }
        return $this;
    }
    
    /**
     * Validate required fields
     *
     * @param array $fields Required fields
     * @return $this For method chaining
     */
    public function required($fields) {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
                $this->errors[$field] = ucfirst($field) . ' is required';
            }
        }
        return $this;
    }
    
    /**
     * Validate email format
     *
     * @param string $field Field name
     * @return $this For method chaining
     */
    public function email($field) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = 'Invalid email format';
            }
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     *
     * @param string $field Field name
     * @param int $length Minimum length
     * @return $this For method chaining
     */
    public function minLength($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = ucfirst($field) . ' must be at least ' . $length . ' characters';
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     *
     * @param string $field Field name
     * @param int $length Maximum length
     * @return $this For method chaining
     */
    public function maxLength($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = ucfirst($field) . ' must not exceed ' . $length . ' characters';
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     *
     * @param string $field Field name
     * @return $this For method chaining
     */
    public function numeric($field) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = ucfirst($field) . ' must be a number';
        }
        return $this;
    }
    
    /**
     * Validate date format
     *
     * @param string $field Field name
     * @param string $format Date format
     * @return $this For method chaining
     */
    public function date($field, $format = 'Y-m-d') {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = ucfirst($field) . ' must be a valid date in format ' . $format;
            }
        }
        return $this;
    }
    
    /**
     * Validate field matches pattern
     *
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @param string $message Error message
     * @return $this For method chaining
     */
    public function pattern($field, $pattern, $message) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field] = $message;
            }
        }
        return $this;
    }
    
    /**
     * Validate field has allowed value
     *
     * @param string $field Field name
     * @param array $allowedValues Allowed values
     * @return $this For method chaining
     */
    public function inArray($field, $allowedValues) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowedValues)) {
            $this->errors[$field] = ucfirst($field) . ' contains an invalid value';
        }
        return $this;
    }
    
    /**
     * Validate password strength
     *
     * @param string $field Field name
     * @return $this For method chaining
     */
    public function password($field) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $password = $this->data[$field];
            
            if (strlen($password) < 8) {
                $this->errors[$field] = 'Password must be at least 8 characters long';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $this->errors[$field] = 'Password must include at least one uppercase letter';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $this->errors[$field] = 'Password must include at least one lowercase letter';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $this->errors[$field] = 'Password must include at least one number';
            }
        }
        return $this;
    }
    
    /**
     * Validate fields match
     *
     * @param string $field1 First field
     * @param string $field2 Second field
     * @param string $message Error message
     * @return $this For method chaining
     */
    public function matches($field1, $field2, $message) {
        if (isset($this->data[$field1]) && isset($this->data[$field2])) {
            if ($this->data[$field1] !== $this->data[$field2]) {
                $this->errors[$field2] = $message;
            }
        }
        return $this;
    }
}