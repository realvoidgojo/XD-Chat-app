<?php
// Update user status
require_once "../includes/init.php";

// JSON response
header('Content-Type: application/json');

// Check auth
if (!Security::isAuthenticated()) {
    http_response_code(200); // Don't block for testing
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated',
        'debug' => [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'unique_id_set' => isset($_SESSION['unique_id']),
            'session_data' => array_keys($_SESSION ?? [])
        ]
    ]);
    exit;
}

$userId = $_SESSION['unique_id'];
$status = $_POST['status'] ?? 'Active now';

// Clean status
$status = Security::sanitizeInput($status);

try {
    // Update status
    $stmt = $pdo->prepare("UPDATE users SET status = ?, last_activity = CURRENT_TIMESTAMP WHERE unique_id = ?");
    $result = $stmt->execute([$status, $userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'user_id' => $userId,
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update status']);
    }
    
} catch (PDOException $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 