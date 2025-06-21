<?php
// App initialization
ob_start();

// Check headers
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line. Cannot initialize session properly.");
}

// Error reporting
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 0);

// Include config
require_once __DIR__ . '/../config/database.php';

// Include Security
require_once __DIR__ . '/Security.php';

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    // Session params
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 7200);
    
    session_name('XD_CHAT_SESSION');
    session_start();
    
    // Session regeneration
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Security headers
if (!headers_sent()) {
    // Content type for non-API
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/php/') === false && strpos($requestUri, '/api/') === false) {
        header("Content-Type: text/html; charset=UTF-8");
    }
    
    // Permissions policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

// Safe redirect
function safeRedirect($url) {
    // Clean buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href = '" . htmlspecialchars($url) . "';</script>";
        exit;
    }
}

// Get current page
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

// Check auth pages
function isAuthPage() {
    $page = getCurrentPage();
    return in_array($page, ['index.php', 'login.php', 'signup.php']);
}

// Check API request
function isApiRequest() {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($requestUri, '/php/') !== false || strpos($requestUri, '/api/') !== false;
}
?> 