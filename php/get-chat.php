<?php 
/**
 * Get Chat Messages API
 * Retrieves chat messages between two users
 */

// Include initialization
require_once "../includes/init.php";

// Check if user is authenticated
if (!Security::isAuthenticated()) {
    echo "Please login first";
    exit;
}

$outgoing_id = $_SESSION['unique_id'];
$incoming_id = $_POST['incoming_id'] ?? '';

if (empty($incoming_id)) {
    echo '<div class="error-message">Invalid user ID</div>';
    exit;
}

$output = "";

try {
    // Get messages between the two users (PostgreSQL compatible)
    $sql = "SELECT * FROM messages 
            WHERE (outgoing_msg_id = ? AND incoming_msg_id = ?) 
               OR (outgoing_msg_id = ? AND incoming_msg_id = ?) 
            ORDER BY msg_id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$outgoing_id, $incoming_id, $incoming_id, $outgoing_id]);
    $messages = $stmt->fetchAll();
    
    if (count($messages) > 0) {
        foreach ($messages as $message) {
            $messageText = Security::escapeOutput($message['msg']);
            $messageTime = date('H:i', strtotime($message['created_at']));
            
            if ($message['outgoing_msg_id'] === $outgoing_id) {
                // Outgoing message (from current user)
                $output .= '<div class="chat outgoing">
                              <div class="details">
                                <p>' . $messageText . '</p>
                                <span class="time">' . $messageTime . '</span>
                              </div>
                            </div>';
            } else {
                // Incoming message (from other user)  
                $output .= '<div class="chat incoming">
                              <div class="details">
                                <p>' . $messageText . '</p>
                                <span class="time">' . $messageTime . '</span>
                              </div>
                            </div>';
            }
        }
    } else {
        $output = '<div class="no-messages">
                     <i class="fas fa-comments"></i>
                     <p>No messages yet</p>
                     <small>Start the conversation by sending a message</small>
                   </div>';
    }
    
} catch (PDOException $e) {
    error_log("Get chat error: " . $e->getMessage());
    $output = '<div class="error-message">
                 <i class="fas fa-exclamation-triangle"></i>
                 <p>Unable to load messages</p>
               </div>';
}

echo $output;
?>