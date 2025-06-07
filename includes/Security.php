<?php
/**
 * Security Utilities Class
 * XD Chat App
 */

class Security {
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['unique_id']) && !empty($_SESSION['unique_id']);
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth($redirectTo = 'login.php') {
        if (!self::isAuthenticated()) {
            header("Location: " . $redirectTo);
            exit;
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['CSRF_TOKEN'])) {
            $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['CSRF_TOKEN'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['CSRF_TOKEN']) && hash_equals($_SESSION['CSRF_TOKEN'], $token);
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Validate input data
     */
    public static function validateInput($input, $type, $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT, $options) !== false;
            case 'password':
                return strlen($input) >= 8 && 
                       preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $input);
            case 'name':
                return preg_match('/^[a-zA-Z\s\-\'\.]{1,50}$/', $input);
            case 'filename':
                return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $input);
            default:
                return !empty(trim($input));
        }
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check rate limiting for login attempts
     */
    public static function checkRateLimit($identifier, $maxAttempts = null, $timeWindow = null) {
        $maxAttempts = $maxAttempts ?? 5;
        $timeWindow = $timeWindow ?? 900;
        
        $key = 'login_attempts_' . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
            return true;
        }
        
        return $attempts['count'] < $maxAttempts;
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedAttempt($identifier) {
        $key = 'login_attempts_' . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $_SESSION[$key]['count']++;
    }
    
    /**
     * Clear rate limit for successful login
     */
    public static function clearRateLimit($identifier) {
        $key = 'login_attempts_' . hash('sha256', $identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Validate uploaded file
     */
    public static function validateUploadedFile($file) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        $maxFileSize = 1048576; // 1MB
        if ($file['size'] > $maxFileSize) {
            $errors[] = 'File size exceeds limit (1MB)';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed';
        }
        
        $pathInfo = pathinfo($file['name']);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension';
        }
        
        // Additional security check - verify image
        if (empty($errors)) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'File is not a valid image';
            }
        }
        
        return $errors;
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName) {
        $pathInfo = pathinfo($originalName);
        $extension = strtolower($pathInfo['extension'] ?? '');
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        
        return $timestamp . '_' . $randomString . '.' . $extension;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * Escape output for safe HTML display
     */
    public static function escapeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Generate nonce for CSP
     */
    public static function generateNonce() {
        return base64_encode(random_bytes(16));
    }
}
?> 