<?php
/**
 * Application Initialization
 * This file should be included first in all PHP pages
 * It handles session initialization before any output
 */

// Prevent any accidental output
ob_start();

// Ensure no output before this point
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line. Cannot initialize session properly.");
}

// Set error reporting for development
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 0);

// Include configuration first (defines constants)
require_once __DIR__ . '/../config/database.php';

// Include Security class
require_once __DIR__ . '/Security.php';

// Initialize secure session ONLY if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session parameters before starting
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

// Set additional security headers only if not already set by .htaccess
// Note: Most security headers are now handled by .htaccess for better performance
if (!headers_sent()) {
    // Only set content-type for non-API requests
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/php/') === false && strpos($requestUri, '/api/') === false) {
        header("Content-Type: text/html; charset=UTF-8");
    }
    
    // Set permissions policy (not handled by .htaccess)
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

/**
 * Helper function to safely redirect
 */
function safeRedirect($url) {
    // Clean any output buffer content first
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

/**
 * Helper function to get current page name
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Helper function to check if user is on login/signup pages
 */
function isAuthPage() {
    $page = getCurrentPage();
    return in_array($page, ['index.php', 'login.php', 'signup.php']);
}

/**
 * Helper function to check if this is an API request
 */
function isApiRequest() {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($requestUri, '/php/') !== false || strpos($requestUri, '/api/') !== false;
}
?> 