<?php
// Format user data for display
try {
    // Get last message
    $sql2 = "SELECT * FROM messages 
             WHERE (incoming_msg_id = ? OR outgoing_msg_id = ?) 
             AND (outgoing_msg_id = ? OR incoming_msg_id = ?) 
             ORDER BY msg_id DESC LIMIT 1";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$user['unique_id'], $user['unique_id'], $outgoing_id, $outgoing_id]);
    $lastMessage = $stmt2->fetch();
    
    if ($lastMessage) {
        $result = $lastMessage['msg'];
        $msg = (strlen($result) > 28) ? substr($result, 0, 28) . '...' : $result;
        $you = ($outgoing_id == $lastMessage['outgoing_msg_id']) ? "You: " : "";
    } else {
        $result = "No message available";
        $msg = $result;
        $you = "";
    }
    
    // Check online status
    $isOnline = true;
    $currentTime = time();
    
    // Get best timestamp
    $lastActivityTime = null;
    if (!empty($user['last_activity'])) {
        $lastActivityTime = strtotime($user['last_activity']);
    } elseif (!empty($user['updated_at'])) {
        $lastActivityTime = strtotime($user['updated_at']);
    } elseif (!empty($user['created_at'])) {
        $lastActivityTime = strtotime($user['created_at']);
    } else {
        // Default to offline
        $lastActivityTime = $currentTime - 600;
    }
    
    // Mark offline if:
    // 1. Status is "Offline"
    // 2. Inactive > 5 minutes
    // 3. Away > 2 minutes
    if ($user['status'] === "Offline" || 
        ($currentTime - $lastActivityTime) > 300 || // 5 minutes
        ($user['status'] === "Away" && ($currentTime - $lastActivityTime) > 120)) { // 2 minutes
        $isOnline = false;
    }
    
    $offline = $isOnline ? "" : "offline";
    $statusText = $isOnline ? $user['status'] : "Offline";
    
    // Hide current user
    $hid_me = ($outgoing_id == $user['unique_id']) ? "hide" : "";
    
    // Build HTML
    $output .= '<a href="chat.php?user_id=' . Security::escapeOutput($user['unique_id']) . '" class="user-item ' . $hid_me . '">
                <div class="content">
                    <img src="uploads/' . Security::escapeOutput($user['img']) . '" alt="Profile Picture" onerror="this.src=\'uploads/default-avatar.png\'">
                    <div class="details">
                        <span>' . Security::escapeOutput($user['fname'] . " " . $user['lname']) . '</span>
                        <p>' . Security::escapeOutput($you . $msg) . '</p>
                    </div>
                </div>
                <div class="status-info">
                    <div class="status-dot ' . $offline . '" title="' . Security::escapeOutput($statusText) . '">
                        <i class="fas fa-circle"></i>
                    </div>
                    <span class="status-text">' . Security::escapeOutput($statusText) . '</span>
                </div>
            </a>';
            
} catch (PDOException $e) {
    error_log("Data formatting error: " . $e->getMessage());
    // Basic user info fallback
    $output .= '<a href="chat.php?user_id=' . Security::escapeOutput($user['unique_id']) . '" class="user-item">
                <div class="content">
                    <img src="uploads/' . Security::escapeOutput($user['img']) . '" alt="Profile Picture" onerror="this.src=\'uploads/default-avatar.png\'">
                    <div class="details">
                        <span>' . Security::escapeOutput($user['fname'] . " " . $user['lname']) . '</span>
                        <p>Click to start chatting</p>
                    </div>
                </div>
                <div class="status-dot">
                    <i class="fas fa-circle"></i>
                </div>
            </a>';
}
?>