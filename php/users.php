<?php
// Users list API
require_once "../includes/init.php";

// Check auth
if (!Security::isAuthenticated()) {
    echo "Please login first";
    exit;
}

$outgoing_id = $_SESSION['unique_id'];

try {
    // Get all users except current
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
            // Format user data
            include "data.php";
        }
    }
    
    echo $output;
    
} catch (PDOException $e) {
    error_log("Users list error: " . $e->getMessage());
    echo "Error loading users";
}
?>