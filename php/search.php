<?php
// User search API
require_once "../includes/init.php";

// Check auth
if (!Security::isAuthenticated()) {
    echo "Please login first";
    exit;
}

$outgoing_id = $_SESSION['unique_id'];
$searchTerm = Security::sanitizeInput($_POST['searchTerm'] ?? '');

$output = "";

try {
    if (empty($searchTerm)) {
        echo "Enter a name to search for users";
        exit;
    }
    
    if (strlen($searchTerm) < 2) {
        echo "Search term must be at least 2 characters long";
        exit;
    }
    
    // Search users by name
    $searchPattern = '%' . $searchTerm . '%';
    $sql = "SELECT unique_id, fname, lname, img, status, last_activity 
            FROM users 
            WHERE unique_id != ? 
            AND is_active = TRUE 
            AND (fname ILIKE ? OR lname ILIKE ? OR (fname || ' ' || lname) ILIKE ?)
            ORDER BY fname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$outgoing_id, $searchPattern, $searchPattern, $searchPattern]);
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            // Format user data
            include "data.php";
        }
    } else {
        $output .= '<div class="no-results">
                      <i class="fas fa-search"></i>
                      <p>No users found matching "' . Security::escapeOutput($searchTerm) . '"</p>
                      <small>Try searching with a different name</small>
                   </div>';
    }
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    $output = '<div class="error-message">
                 <i class="fas fa-exclamation-triangle"></i>
                 <p>Search temporarily unavailable</p>
                 <small>Please try again later</small>
               </div>';
}

echo $output;
?>