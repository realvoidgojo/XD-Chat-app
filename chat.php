<?php 
/**
 * Chat Page
 * XD Chat App
 */

// Initialize application (session, database, security)
require_once 'includes/init.php';

// Require authentication
Security::requireAuth();

// Get current user
$currentUserId = $_SESSION['unique_id'];

// Get target user ID from URL parameter
$targetUserId = $_GET['user_id'] ?? '';

if (empty($targetUserId)) {
    safeRedirect("users.php");
}

try {
    // Get target user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE unique_id = ? AND is_active = TRUE");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        safeRedirect("users.php");
    }

} catch (PDOException $e) {
    error_log("Chat page error: " . $e->getMessage());
    safeRedirect("users.php");
}

// Define page title
define('PAGE_TITLE', 'Chat with ' . $targetUser['fname'] . ' - XD Chat App');
?>

<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="chat-area">
      <header>
        <a href="users.php" class="back-icon"><i class="fas fa-arrow-left"></i></a>
        <img src="uploads/<?php echo htmlspecialchars($targetUser['img']); ?>" alt="Profile Picture" onerror="this.src='uploads/default-avatar.png'">
        <div class="details">
          <span><?php echo htmlspecialchars($targetUser['fname'] . " " . $targetUser['lname']); ?></span>
          <p><?php echo htmlspecialchars($targetUser['status']); ?></p>
        </div>
        <div class="chat-actions">
          <button class="user-info-btn" title="User Info">
            <i class="fas fa-info-circle"></i>
          </button>
        </div>
      </header>

      <div class="chat-box" id="chatBox">
        <div class="loading-messages">
          <i class="fas fa-spinner fa-spin"></i>
          Loading messages...
        </div>
      </div>

      <form action="#" class="typing-area" id="messageForm">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        <input type="hidden" class="incoming_id" name="incoming_id" value="<?php echo htmlspecialchars($targetUserId); ?>">
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off" maxlength="1000" required>
        <button type="submit" disabled><i class="fab fa-telegram-plane"></i></button>
      </form>
    </section>
  </div>

  <!-- User Info Modal -->
  <div class="user-info-modal" id="userInfoModal" style="display: none;">
    <div class="modal-content">
      <span class="close-modal">&times;</span>
      <h3>User Information</h3>
      <div class="user-info-content">
        <img src="uploads/<?php echo htmlspecialchars($targetUser['img']); ?>" alt="Profile Picture" onerror="this.src='uploads/default-avatar.png'">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($targetUser['fname'] . " " . $targetUser['lname']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($targetUser['email']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($targetUser['status']); ?></p>
        <p><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($targetUser['created_at'])); ?></p>
      </div>
    </div>
  </div>

  <script src="javascript/chat.js"></script>

  <script>
    // User info modal functionality
    document.querySelector('.user-info-btn').addEventListener('click', function() {
      document.getElementById('userInfoModal').style.display = 'block';
    });

    document.querySelector('.close-modal').addEventListener('click', function() {
      document.getElementById('userInfoModal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
      const modal = document.getElementById('userInfoModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });
  </script>
</body>
</html>
```
