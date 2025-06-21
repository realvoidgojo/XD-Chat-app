<?php
// Message model
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Security.php';

class Message {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    // Create message
    public function create($outgoingId, $incomingId, $message) {
        try {
            // Clean input
            $message = Security::sanitizeInput($message, 'string');
            
            if (empty(trim($message))) {
                return ['success' => false, 'error' => 'Message cannot be empty'];
            }
            
            if (strlen($message) > 1000) {
                return ['success' => false, 'error' => 'Message too long (max 1000 characters)'];
            }
            
            $sql = "INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, created_at) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$incomingId, $outgoingId, $message]);
            
            if ($result) {
                return ['success' => true, 'message_id' => $this->pdo->lastInsertId()];
            } else {
                return ['success' => false, 'error' => 'Failed to send message'];
            }
            
        } catch (PDOException $e) {
            error_log("Message creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    // Get conversation
    public function getConversation($userId1, $userId2, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT m.*, u.fname, u.lname, u.img, m.created_at 
                    FROM messages m 
                    LEFT JOIN users u ON u.unique_id = m.outgoing_msg_id
                    WHERE (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?) 
                    OR (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?) 
                    ORDER BY m.msg_id ASC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit, $offset]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get conversation error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get last message
    public function getLastMessage($userId1, $userId2) {
        try {
            $sql = "SELECT m.*, u.fname, u.lname 
                    FROM messages m 
                    LEFT JOIN users u ON u.unique_id = m.outgoing_msg_id
                    WHERE (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?) 
                    OR (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?) 
                    ORDER BY m.msg_id DESC 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get last message error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get recent chats
    public function getRecentConversations($userId, $limit = 20) {
        try {
            $sql = "SELECT DISTINCT 
                        CASE 
                            WHEN m.outgoing_msg_id = ? THEN m.incoming_msg_id 
                            ELSE m.outgoing_msg_id 
                        END as other_user_id,
                        u.fname, u.lname, u.img, u.status,
                        MAX(m.created_at) as last_message_time,
                        (SELECT msg FROM messages m2 
                         WHERE (m2.outgoing_msg_id = ? AND m2.incoming_msg_id = other_user_id) 
                         OR (m2.outgoing_msg_id = other_user_id AND m2.incoming_msg_id = ?)
                         ORDER BY m2.msg_id DESC LIMIT 1) as last_message
                    FROM messages m
                    LEFT JOIN users u ON u.unique_id = CASE 
                        WHEN m.outgoing_msg_id = ? THEN m.incoming_msg_id 
                        ELSE m.outgoing_msg_id 
                    END
                    WHERE m.outgoing_msg_id = ? OR m.incoming_msg_id = ?
                    GROUP BY other_user_id
                    ORDER BY last_message_time DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get recent conversations error: " . $e->getMessage());
            return [];
        }
    }
    
    // Mark as read
    public function markAsRead($userId, $otherUserId) {
        try {
            $sql = "UPDATE messages SET is_read = 1 
                    WHERE incoming_msg_id = ? AND outgoing_msg_id = ? AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$userId, $otherUserId]);
            
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get unread count
    public function getUnreadCount($userId, $otherUserId = null) {
        try {
            if ($otherUserId) {
                // Count from specific user
                $sql = "SELECT COUNT(*) FROM messages 
                        WHERE incoming_msg_id = ? AND outgoing_msg_id = ? AND is_read = 0";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId, $otherUserId]);
            } else {
                // Total unread
                $sql = "SELECT COUNT(*) FROM messages WHERE incoming_msg_id = ? AND is_read = 0";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId]);
            }
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Delete message
    public function delete($messageId, $userId) {
        try {
            // Only own messages
            $sql = "UPDATE messages SET is_deleted = 1 
                    WHERE msg_id = ? AND outgoing_msg_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$messageId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Delete message error: " . $e->getMessage());
            return false;
        }
    }
    
    // Search messages
    public function search($userId, $searchTerm, $limit = 50) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            
            $sql = "SELECT m.*, u.fname, u.lname, u.img 
                    FROM messages m 
                    LEFT JOIN users u ON u.unique_id = m.outgoing_msg_id
                    WHERE (m.outgoing_msg_id = ? OR m.incoming_msg_id = ?) 
                    AND m.msg LIKE ? AND m.is_deleted = 0
                    ORDER BY m.msg_id DESC 
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $userId, $searchTerm, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Search messages error: " . $e->getMessage());
            return [];
        }
    }
}
?> 