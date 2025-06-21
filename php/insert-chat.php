<?php 
// Send message
require_once "../includes/init.php";

// Check auth
if (!Security::isAuthenticated()) {
    echo "error";
    exit;
}

// Check CSRF
if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo "error";
    exit;
}

$outgoing_id = $_SESSION['unique_id'];
$incoming_id = Security::sanitizeInput($_POST['incoming_id'] ?? '');
$message = Security::sanitizeInput($_POST['message'] ?? '');

// Check input
if (empty($incoming_id) || empty($message)) {
    echo "error";
    exit;
}

// Check length
if (strlen($message) > 1000) {
    echo "error";
    exit;
}

try {
    // User exists?
    $stmt = $pdo->prepare("SELECT unique_id FROM users WHERE unique_id = ? AND is_active = TRUE");
    $stmt->execute([$incoming_id]);
    
    if (!$stmt->fetch()) {
        echo "error";
        exit;
    }
    
    // Insert message
    $sql = "INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, created_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$incoming_id, $outgoing_id, $message]);
    
    if ($result) {
        echo "success";
    } else {
        echo "error";
    }
    
} catch (PDOException $e) {
    error_log("Insert chat error: " . $e->getMessage());
    echo "error";
}
?>