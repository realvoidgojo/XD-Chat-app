<?php
/**
 * User Data Formatting
 * Formats user data for display in users list
 * $user array is passed from parent file
 */

try {
    // Get the last message between current user and this user (PostgreSQL compatible)
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
    
    // Determine offline status - more comprehensive check
    $isOnline = true;
    $currentTime = time();
    
    // Use the best available timestamp for determining online status
    $lastActivityTime = null;
    if (!empty($user['last_activity'])) {
        $lastActivityTime = strtotime($user['last_activity']);
    } elseif (!empty($user['updated_at'])) {
        $lastActivityTime = strtotime($user['updated_at']);
    } elseif (!empty($user['created_at'])) {
        $lastActivityTime = strtotime($user['created_at']);
    } else {
        // Default to current time minus 10 minutes to mark as offline
        $lastActivityTime = $currentTime - 600;
    }
    
    // Consider user offline if:
    // 1. Status is explicitly "Offline"
    // 2. Last activity was more than 5 minutes ago
    // 3. Status is "Away" and last activity was more than 2 minutes ago
    if ($user['status'] === "Offline" || 
        ($currentTime - $lastActivityTime) > 300 || // 5 minutes
        ($user['status'] === "Away" && ($currentTime - $lastActivityTime) > 120)) { // 2 minutes for Away
        $isOnline = false;
    }
    
    $offline = $isOnline ? "" : "offline";
    $statusText = $isOnline ? $user['status'] : "Offline";
    
    // Don't show current user in the list
    $hid_me = ($outgoing_id == $user['unique_id']) ? "hide" : "";
    
    // Build the output HTML with proper escaping
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
    // Continue with basic user info if message query fails
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