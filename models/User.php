<?php
// User model
class User {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    // Create user
    public function create($data) {
        try {
            // Validate input
            $errors = $this->validateUserData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Email exists?
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'errors' => ['Email already exists']];
            }
            
            // Generate ID
            $uniqueId = $this->generateUniqueId();
            
            // Hash password
            $hashedPassword = Security::hashPassword($data['password']);
            
            // Insert user
            $sql = "INSERT INTO users (unique_id, fname, lname, email, password, img, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Active now', CURRENT_TIMESTAMP)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $uniqueId,
                $data['fname'],
                $data['lname'],
                $data['email'],
                $hashedPassword,
                $data['img']
            ]);
            
            if ($result) {
                return ['success' => true, 'user_id' => $uniqueId];
            } else {
                return ['success' => false, 'errors' => ['Failed to create user']];
            }
            
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Database error occurred']];
        }
    }
    
    // Authenticate user
    public function authenticate($email, $password) {
        try {
            // Check rate limit
            if (!Security::checkRateLimit($email)) {
                return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
            }
            
            $sql = "SELECT * FROM users WHERE email = ? AND is_active = TRUE";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && Security::verifyPassword($password, $user['password'])) {
                // Clear rate limit
                Security::clearRateLimit($email);
                
                // Update last login
                $this->updateLastLogin($user['unique_id']);
                
                return ['success' => true, 'user' => $user];
            } else {
                // Record fail
                Security::recordFailedAttempt($email);
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    // Get user by ID
    public function getById($uniqueId) {
        try {
            $sql = "SELECT * FROM users WHERE unique_id = ? AND is_active = TRUE";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$uniqueId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all users
    public function getAllExcept($currentUserId) {
        try {
            $sql = "SELECT unique_id, fname, lname, img, status FROM users WHERE unique_id != ? AND is_active = TRUE ORDER BY fname ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentUserId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Search users
    public function search($searchTerm, $currentUserId) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            // Use ILIKE for case-insensitive
            $sql = "SELECT unique_id, fname, lname, img, status FROM users 
                    WHERE (fname ILIKE ? OR lname ILIKE ? OR (fname || ' ' || lname) ILIKE ?) 
                    AND unique_id != ? AND is_active = TRUE ORDER BY fname ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $currentUserId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Update status
    public function updateStatus($uniqueId, $status) {
        try {
            $sql = "UPDATE users SET status = ?, last_activity = CURRENT_TIMESTAMP WHERE unique_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$status, $uniqueId]);
        } catch (PDOException $e) {
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login
     */
    private function updateLastLogin($uniqueId) {
        try {
            $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE unique_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$uniqueId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique ID
     */
    private function generateUniqueId() {
        do {
            $uniqueId = rand(100000000, 999999999);
            $sql = "SELECT COUNT(*) FROM users WHERE unique_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$uniqueId]);
        } while ($stmt->fetchColumn() > 0);
        
        return $uniqueId;
    }
    
    /**
     * Validate user data
     */
    private function validateUserData($data) {
        $errors = [];
        
        // Validate required fields
        $required = ['fname', 'lname', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        
        // Validate email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate password
        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Validate names
        if (!empty($data['fname']) && !Security::validateInput($data['fname'], 'name')) {
            $errors[] = 'Invalid first name format';
        }
        
        if (!empty($data['lname']) && !Security::validateInput($data['lname'], 'name')) {
            $errors[] = 'Invalid last name format';
        }
        
        return $errors;
    }
}
?> 