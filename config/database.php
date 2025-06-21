<?php
// Database config
require_once __DIR__ . '/supabase_config.php';

// DB settings
if (!defined('DB_HOST')) {
    define("DB_HOST", $_ENV['SUPABASE_HOST'] ?? getenv('SUPABASE_HOST') ?? "localhost");
    define("DB_PORT", $_ENV['SUPABASE_PORT'] ?? getenv('SUPABASE_PORT') ?? "5432");
    define("DB_USERNAME", $_ENV['SUPABASE_USERNAME'] ?? getenv('SUPABASE_USERNAME') ?? "");
    define("DB_PASSWORD", $_ENV['SUPABASE_PASSWORD'] ?? getenv('SUPABASE_PASSWORD') ?? "");
    define("DB_NAME", $_ENV['SUPABASE_DATABASE'] ?? getenv('SUPABASE_DATABASE') ?? "postgres");
}
define("DB_CHARSET", "utf8");

// App constants
define("APP_NAME", "XD Chat App");
define("APP_VERSION", "2.0.0");
define("UPLOAD_DIR", __DIR__ . "/../uploads/");
define("MAX_FILE_SIZE", 1048576); // 1MB
define("ALLOWED_IMAGE_TYPES", ["image/jpeg", "image/jpg", "image/png", "image/gif"]);
define("ALLOWED_IMAGE_EXTENSIONS", ["jpeg", "jpg", "png", "gif"]);

// Session settings
define("SESSION_LIFETIME", 7200); // 2 hours
define("SESSION_NAME", "XD_CHAT_SESSION");

// Security settings
define("CSRF_TOKEN_NAME", "csrf_token");
define("PASSWORD_MIN_LENGTH", 8);
define("LOGIN_ATTEMPTS_LIMIT", 5);
define("LOGIN_LOCKOUT_TIME", 900); // 15 minutes

// Get DB connection
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // PostgreSQL DSN
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;options='--client_encoding=" . DB_CHARSET . "'";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30, // 30 second timeout
            ];
            
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
            // Set timezone
            $pdo->exec("SET TIME ZONE 'UTC'");
            $pdo->exec("SET NAMES 'UTF8'");
            
        } catch (PDOException $e) {
            $error_msg = "Database connection failed: " . $e->getMessage();
            $debug_info = " | Host: " . DB_HOST . " | Port: " . DB_PORT . " | Database: " . DB_NAME . " | User: " . DB_USERNAME;
            error_log($error_msg . $debug_info);
            
            // Debug mode
            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                throw new Exception($error_msg . $debug_info);
            }
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// Global PDO for compatibility
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    // API endpoints
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/php/') !== false || 
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    // Regular pages
    die("Database connection failed. Please try again later.");
}
?>