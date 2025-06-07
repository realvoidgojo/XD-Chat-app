<?php
/**
 * Chat Controller
 * XD Chat App
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/User.php';

class ChatController {
    private $message;
    private $user;
    
    public function __construct() {
        Security::initSecureSession();
        Security::requireAuth();
        $this->message = new Message();
        $this->user = new User();
    }
    
    /**
     * Send a new message
     */
    public function sendMessage() {
        header('Content-Type: application/json');
        
        try {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid security token']);
                return;
            }
            
            // Validate required fields
            if (!isset($_POST['incoming_id']) || !isset($_POST['message']) || 
                empty(trim($_POST['message']))) {
                echo json_encode(['success' => false, 'error' => 'Message and recipient are required']);
                return;
            }
            
            $incomingId = Security::sanitizeInput($_POST['incoming_id'], 'int');
            $messageText = $_POST['message']; // Will be sanitized in the model
            $outgoingId = $_SESSION['user_id'];
            
            // Validate incoming user ID
            if (!Security::validateInput($incomingId, 'int', ['min_range' => 1])) {
                echo json_encode(['success' => false, 'error' => 'Invalid recipient']);
                return;
            }
            
            // Check if recipient exists
            $recipient = $this->user->getById($incomingId);
            if (!$recipient) {
                echo json_encode(['success' => false, 'error' => 'Recipient not found']);
                return;
            }
            
            // Send message
            $result = $this->message->create($outgoingId, $incomingId, $messageText);
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'message_id' => $result['message_id']]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            
        } catch (Exception $e) {
            error_log("Send message error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    }
    
    /**
     * Get conversation messages
     */
    public function getMessages() {
        header('Content-Type: text/html; charset=UTF-8');
        
        try {
            if (!isset($_POST['incoming_id'])) {
                echo '<div class="text">Invalid request</div>';
                return;
            }
            
            $incomingId = Security::sanitizeInput($_POST['incoming_id'], 'int');
            $outgoingId = $_SESSION['user_id'];
            
            // Validate incoming user ID
            if (!Security::validateInput($incomingId, 'int', ['min_range' => 1])) {
                echo '<div class="text">Invalid user ID</div>';
                return;
            }
            
            // Get messages
            $messages = $this->message->getConversation($outgoingId, $incomingId);
            
            if (empty($messages)) {
                echo '<div class="text">No messages are available. Once you send message they will appear here.</div>';
                return;
            }
            
            // Mark messages as read
            $this->message->markAsRead($outgoingId, $incomingId);
            
            foreach ($messages as $row) {
                $isOutgoing = $row['outgoing_msg_id'] == $outgoingId;
                $messageText = Security::escapeOutput($row['msg']);
                
                if ($isOutgoing) {
                    echo '<div class="chat outgoing">
                            <div class="details">
                                <p>' . $messageText . '</p>
                                <span class="time">' . date('H:i', strtotime($row['created_at'])) . '</span>
                            </div>
                          </div>';
                } else {
                    $userImage = Security::escapeOutput($row['img']);
                    echo '<div class="chat incoming">
                            <img src="uploads/' . $userImage . '" alt="User Avatar">
                            <div class="details">
                                <p>' . $messageText . '</p>
                                <span class="time">' . date('H:i', strtotime($row['created_at'])) . '</span>
                            </div>
                          </div>';
                }
            }
            
        } catch (Exception $e) {
            error_log("Get messages error: " . $e->getMessage());
            echo '<div class="text">Error loading messages</div>';
        }
    }
    
    /**
     * Search messages
     */
    public function searchMessages() {
        header('Content-Type: application/json');
        
        try {
            if (!isset($_POST['search_term']) || empty(trim($_POST['search_term']))) {
                echo json_encode(['success' => false, 'error' => 'Search term is required']);
                return;
            }
            
            $searchTerm = Security::sanitizeInput($_POST['search_term']);
            $userId = $_SESSION['user_id'];
            
            $messages = $this->message->search($userId, $searchTerm);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            
        } catch (Exception $e) {
            error_log("Search messages error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Search failed']);
        }
    }
    
    /**
     * Delete a message
     */
    public function deleteMessage() {
        header('Content-Type: application/json');
        
        try {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid security token']);
                return;
            }
            
            if (!isset($_POST['message_id'])) {
                echo json_encode(['success' => false, 'error' => 'Message ID is required']);
                return;
            }
            
            $messageId = Security::sanitizeInput($_POST['message_id'], 'int');
            $userId = $_SESSION['user_id'];
            
            // Validate message ID
            if (!Security::validateInput($messageId, 'int', ['min_range' => 1])) {
                echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
                return;
            }
            
            $result = $this->message->delete($messageId, $userId);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
            }
            
        } catch (Exception $e) {
            error_log("Delete message error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
        }
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $otherUserId = isset($_POST['other_user_id']) ? 
                Security::sanitizeInput($_POST['other_user_id'], 'int') : null;
            
            $count = $this->message->getUnreadCount($userId, $otherUserId);
            
            echo json_encode(['success' => true, 'count' => $count]);
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to get unread count']);
        }
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new ChatController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'send':
            $controller->sendMessage();
            break;
        case 'get':
            $controller->getMessages();
            break;
        case 'search':
            $controller->searchMessages();
            break;
        case 'delete':
            $controller->deleteMessage();
            break;
        case 'unread':
            $controller->getUnreadCount();
            break;
        default:
            // Default to getting messages for backward compatibility
            $controller->getMessages();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?> 