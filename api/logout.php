<?php
/**
 * Logout API Endpoint
 * XD Chat App
 */

// Include necessary files
require_once "../config/database.php";
require_once "../includes/Security.php";

// Initialize secure session
Security::initSecureSession();

// Set content type for JSON response
header('Content-Type: application/json');

try {
    // Check if user is authenticated
    if (!isset($_SESSION['unique_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['unique_id'];
    
    // Update user status to offline before logout
    $sql = "UPDATE users SET status = 'Offline', last_activity = NOW() WHERE unique_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => 'login.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Logout database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error during logout']);
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred during logout']);
}
?> 