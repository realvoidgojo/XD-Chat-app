<?php
/**
 * Users List API
 * Returns list of users for chat
 */

// Include the correct initialization
require_once "../includes/init.php";

// Check if user is authenticated
if (!Security::isAuthenticated()) {
    echo "Please login first";
    exit;
}

$outgoing_id = $_SESSION['unique_id'];

try {
    // Get all users except current user, ordered by last activity
    $sql = "SELECT unique_id, fname, lname, img, status, last_activity, updated_at, created_at 
            FROM users 
            WHERE unique_id != ? AND is_active = TRUE 
            ORDER BY COALESCE(last_activity, updated_at) DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$outgoing_id]);
    $users = $stmt->fetchAll();
    
    $output = "";
    
    if (count($users) == 0) {
        $output .= "No users are available to chat";
    } else {
        foreach ($users as $user) {
            // Include the user data formatting
            include "data.php";
        }
    }
    
    echo $output;
    
} catch (PDOException $e) {
    error_log("Users list error: " . $e->getMessage());
    echo "Error loading users";
}
?>