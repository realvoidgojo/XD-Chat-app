<?php
/**
 * XD Chat App Database Configuration - PostgreSQL Version
 * Updated for Supabase PostgreSQL deployment
 */

// Database configuration for PostgreSQL (Supabase)
// Using environment variables from Replit Secrets
define("DB_HOST", $_ENV['SUPABASE_HOST'] ?? "localhost");
define("DB_PORT", $_ENV['SUPABASE_PORT'] ?? "6543");
define("DB_USERNAME", $_ENV['SUPABASE_USERNAME'] ?? "");
define("DB_PASSWORD", $_ENV['SUPABASE_PASSWORD'] ?? "");
define("DB_NAME", $_ENV['SUPABASE_DATABASE'] ?? "postgres");
define("DB_CHARSET", "utf8");

// Application constants
define("APP_NAME", "XD Chat App");
define("APP_VERSION", "2.0.0");
define("UPLOAD_DIR", __DIR__ . "/../uploads/");
define("MAX_FILE_SIZE", 1048576); // 1MB
define("ALLOWED_IMAGE_TYPES", ["image/jpeg", "image/jpg", "image/png", "image/gif"]);
define("ALLOWED_IMAGE_EXTENSIONS", ["jpeg", "jpg", "png", "gif"]);

// Session configuration
define("SESSION_LIFETIME", 7200); // 2 hours
define("SESSION_NAME", "XD_CHAT_SESSION");

// Security settings
define("CSRF_TOKEN_NAME", "csrf_token");
define("PASSWORD_MIN_LENGTH", 8);
define("LOGIN_ATTEMPTS_LIMIT", 5);
define("LOGIN_LOCKOUT_TIME", 900); // 15 minutes

// Initialize database connection
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // PostgreSQL DSN format with SSL support for Supabase
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;options='--client_encoding=" . DB_CHARSET . "'";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30, // 30 second timeout for Supabase
            ];
            
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
            // Set PostgreSQL specific settings
            $pdo->exec("SET TIME ZONE 'UTC'");
            $pdo->exec("SET NAMES 'UTF8'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// Create global $pdo variable for backward compatibility
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    // For API endpoints, return JSON error
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/php/') !== false || 
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    // For regular pages, show error message
    die("Database connection failed. Please try again later.");
}
?>