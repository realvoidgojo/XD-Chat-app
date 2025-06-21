<?php 
// Login page
require_once 'includes/init.php';

// Redirect if logged in
if (Security::isAuthenticated()) {
    safeRedirect('users.php');
}

define('PAGE_TITLE', 'Login - XD Chat App');
?>

<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="form login">
      <header>XD Chat App</header>
      <form action="#" method="POST" autocomplete="off" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        
        <div class="error-text" id="errorText" style="display: none;"></div>
        
        <div class="field input">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="Enter your email" required maxlength="100" autocomplete="username">
        </div>
        
        <div class="field input">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required maxlength="100" autocomplete="current-password">
          <i class="fas fa-eye toggle-password"></i>
        </div>
        
        <div class="field button">
          <input type="submit" name="submit" value="Continue to Chat">
        </div>
      </form>
      
      <div class="link">
        Not yet signed up? <a href="index.php">Signup now</a>
      </div>
    </section>
  </div>

  <script src="javascript/pass-show-hide.js"></script>
  <script src="javascript/login.js"></script>
</body>
</html>
