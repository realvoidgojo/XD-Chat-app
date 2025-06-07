<?php 
/**
 * Signup Page
 * XD Chat App
 */

// Initialize application (session, database, security)
require_once 'includes/init.php';

// Redirect if already logged in
if (Security::isAuthenticated()) {
    safeRedirect('users.php');
}

// Define page title
define('PAGE_TITLE', 'Signup - XD Chat App');
?>

<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="form signup">
      <header>XD Chat App</header>
      <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off" id="signupForm">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        
        <div class="error-text" id="errorText" style="display: none;"></div>
        
        <div class="name-details">
          <div class="field input">
            <label>First Name</label>
            <input type="text" name="fname" placeholder="First name" required maxlength="50" autocomplete="given-name">
          </div>
          <div class="field input">
            <label>Last Name</label>
            <input type="text" name="lname" placeholder="Last name" required maxlength="50" autocomplete="family-name">
          </div>
        </div>
        
        <div class="field input">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="Enter your email" required maxlength="100" autocomplete="email">
        </div>
        
        <div class="field input">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter new password" required maxlength="100" autocomplete="new-password">
          <i class="fas fa-eye toggle-password"></i>
        </div>
        
        <div class="field image">
          <label>Select Image</label>
          <input type="file" name="image" accept="image/*" required>
        </div>
        
        <div class="field button">
          <input type="submit" name="submit" value="Continue to Chat">
        </div>
      </form>
      
      <div class="link">Already signed up? <a href="login.php">Login now</a></div>
    </section>
  </div>

  <script src="javascript/pass-show-hide.js"></script>
  <script src="javascript/signup.js"></script>
</body>
</html>

