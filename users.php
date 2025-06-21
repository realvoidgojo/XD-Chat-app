<?php 
// Users list page
require_once 'includes/init.php';

// Need auth
Security::requireAuth();

$currentUserId = $_SESSION['unique_id'];

// Include User model
require_once 'models/User.php';
$userModel = new User();

// Get users
try {
    $currentUser = $userModel->getById($currentUserId);
    $users = $userModel->getAllExcept($currentUserId);
    
    if (!$currentUser) {
        Security::logout();
        safeRedirect('login.php');
    }
    
} catch (Exception $e) {
    error_log("Users page error: " . $e->getMessage());
    $users = [];
}

// Set status online
$userModel->updateStatus($currentUserId, "Active now");

define('PAGE_TITLE', 'Users - XD Chat App');
?>

<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="users">
      <header>
        <div class="content">
          <img src="uploads/<?php echo htmlspecialchars($currentUser['img']); ?>" alt="Profile Picture" onerror="this.src='uploads/default-avatar.png'">
          <div class="details">
            <span><?php echo htmlspecialchars($currentUser['fname'] . " " . $currentUser['lname']); ?></span>
            <p><?php echo htmlspecialchars($currentUser['status']); ?></p>
          </div>
        </div>
        <div class="actions">
          <button class="profile-btn" title="Profile Settings">
            <i class="fas fa-user-cog"></i>
          </button>
          <a href="php/logout.php" class="logout" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
          </a>
        </div>
      </header>
      
      <div class="search">
        <span class="text">Select a user to start chat</span>
        <div class="search-box">
          <input type="text" placeholder="Enter name to search..." id="searchInput" maxlength="50">
          <button type="button" id="searchBtn"><i class="fas fa-search"></i></button>
          <button type="button" id="clearSearch" style="display: none;"><i class="fas fa-times"></i></button>
        </div>
      </div>
      
      <div class="users-list">
        <?php if (empty($users)): ?>
          <div class="no-users">
            <p>No other users found</p>
          </div>
        <?php else: ?>
          <?php foreach ($users as $user): ?>
            <a href="chat.php?user_id=<?php echo htmlspecialchars($user['unique_id']); ?>">
              <div class="content">
                <img src="uploads/<?php echo htmlspecialchars($user['img']); ?>" alt="Profile Picture" onerror="this.src='uploads/default-avatar.png'">
                <div class="details">
                  <span><?php echo htmlspecialchars($user['fname'] . " " . $user['lname']); ?></span>
                  <p class="user-status"><?php echo htmlspecialchars($user['status']); ?></p>
                </div>
              </div>
              <div class="status-dot <?php echo (strpos($user['status'], 'Active') === 0) ? 'online' : 'offline'; ?>">
                <i class="fas fa-circle"></i>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- Profile modal -->
  <div class="profile-modal" id="profileModal" style="display: none;">
    <div class="modal-content">
      <span class="close-modal">&times;</span>
      <h3>Profile Settings</h3>
      <form id="profileForm" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        
        <div class="form-group">
          <label>Profile Picture</label>
          <div class="image-upload">
            <img id="profile-preview" src="uploads/<?php echo htmlspecialchars($currentUser['img']); ?>" alt="Profile Picture" onerror="this.src='uploads/default-avatar.png'">
            <input type="file" id="profile-image" name="profile_image" accept="image/*">
            <label for="profile-image" class="upload-btn">
              <i class="fas fa-camera"></i> Change Photo
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="fname" value="<?php echo htmlspecialchars($currentUser['fname']); ?>" maxlength="50" required>
        </div>
        
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="lname" value="<?php echo htmlspecialchars($currentUser['lname']); ?>" maxlength="50" required>
        </div>
        
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" maxlength="100" required>
        </div>
        
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="Active now" <?php echo ($currentUser['status'] === 'Active now') ? 'selected' : ''; ?>>Active now</option>
            <option value="Busy" <?php echo ($currentUser['status'] === 'Busy') ? 'selected' : ''; ?>>Busy</option>
            <option value="Away" <?php echo ($currentUser['status'] === 'Away') ? 'selected' : ''; ?>>Away</option>
          </select>
        </div>
        
        <button type="submit" class="btn-primary">Update Profile</button>
      </form>
    </div>
  </div>

  <script src="javascript/users.js"></script>
  <script src="javascript/profile.js"></script>
  
  <script>
    // Profile modal handlers
    document.querySelector('.profile-btn').addEventListener('click', function() {
      document.getElementById('profileModal').style.display = 'block';
    });

    document.querySelector('.close-modal').addEventListener('click', function() {
      document.getElementById('profileModal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
      const modal = document.getElementById('profileModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });

    // Profile image preview
    document.getElementById('profile-image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('profile-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  </script>
</body>
</html>