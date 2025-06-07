<?php
/**
 * Update User Status API
 * Updates user online/offline status
 */

// Include the correct config file
require_once "../config/database.php";
require_once "../includes/Security.php";

// Use the same session initialization
Security::initSecureSession();

// Check if user is authenticated
if (!isset($_SESSION['unique_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$user_id = $_SESSION['unique_id'];
$status = $_POST['status'] ?? 'Active now';

// Validate status
$allowedStatuses = ['Active now', 'Away', 'Offline'];
if (!in_array($status, $allowedStatuses)) {
    $status = 'Active now';
}

try {
    // Update user status and last activity
    $sql = "UPDATE users SET 
            status = ?, 
            last_activity = NOW() 
            WHERE unique_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$status, $user_id]);
    
    if ($result) {
        // Also update session if needed
        $_SESSION['last_activity'] = time();
        
        http_response_code(200);
        echo "success";
    } else {
        http_response_code(500);
        echo "Failed to update status";
    }
    
} catch (PDOException $e) {
    error_log("Status update error: " . $e->getMessage());
    http_response_code(500);
    echo "Database error";
}
?> 