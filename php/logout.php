<?php
/**
 * Logout Handler
 * XD Chat App
 */

// Initialize application
require_once '../includes/init.php';

// Check if user is logged in
if (!Security::isAuthenticated()) {
    safeRedirect('../login.php');
}

$userId = $_SESSION['unique_id'];

try {
    // Update user status to offline
    $stmt = $pdo->prepare("UPDATE users SET status = 'Offline now', last_activity = CURRENT_TIMESTAMP WHERE unique_id = ?");
    $stmt->execute([$userId]);
    
    // Logout user (clear session)
    Security::logout();
    
    // Redirect to login page
    safeRedirect('../login.php');
    
} catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
    
    // Even if database update fails, still logout the user
    Security::logout();
    safeRedirect('../login.php');
}
?>