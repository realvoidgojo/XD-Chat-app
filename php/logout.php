<?php
// Logout handler
require_once '../includes/init.php';

// Check if logged in
if (!Security::isAuthenticated()) {
    safeRedirect('../login.php');
}

$userId = $_SESSION['unique_id'];

try {
    // Set offline status
    $stmt = $pdo->prepare("UPDATE users SET status = 'Offline now', last_activity = CURRENT_TIMESTAMP WHERE unique_id = ?");
    $stmt->execute([$userId]);
    
    // Logout user
    Security::logout();
    
    // Back to login
    safeRedirect('../login.php');
    
} catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
    
    // Logout anyway
    Security::logout();
    safeRedirect('../login.php');
}
?>