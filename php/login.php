<?php 
/**
 * Login Handler - Fixed Session Context
 * Uses same session initialization as form page
 */

// Start output buffering for clean output
ob_start();

// Include the initialization file
require_once "../includes/init.php";

// Clean any previous output buffers except our own
while (ob_get_level() > 1) {
    ob_end_clean();
}

// Set content type for API response
header('Content-Type: text/plain; charset=UTF-8');

$receivedToken = $_POST['csrf_token'] ?? '';

// Verify CSRF token
if (!Security::verifyCSRFToken($receivedToken)) {
    ob_clean();
    echo "Security token invalid. Please try again.";
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!empty($email) && !empty($password)) {
    try {
        // Use prepared statement for security
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password using password_verify (works with Argon2ID)
            if (password_verify($password, $user['password'])) {
                // Update user status to "Active now"
                $updateStmt = $pdo->prepare("UPDATE users SET status = 'Active now', last_login = CURRENT_TIMESTAMP WHERE unique_id = ?");
                $updateStmt->execute([$user['unique_id']]);
                
                // Set session - using both variable names for compatibility
                $_SESSION['unique_id'] = $user['unique_id'];
                $_SESSION['user_id'] = $user['id']; // This is what users.php expects
                $_SESSION['email'] = $user['email'];
                $_SESSION['fname'] = $user['fname'];
                $_SESSION['lname'] = $user['lname'];
                
                // Log successful login
                $logStmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success, attempted_at) VALUES (?, ?, TRUE, CURRENT_TIMESTAMP)");
                $logStmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                // Clean output and send only "success"
                ob_clean();
                echo "success";
                exit;
            } else {
                // Log failed login attempt
                $logStmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success, attempted_at) VALUES (?, ?, FALSE, CURRENT_TIMESTAMP)");
                $logStmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                ob_clean();
                echo "Email or Password is Incorrect!";
                exit;
            }
        } else {
            ob_clean();
            echo "$email - This email not Exist!";
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        ob_clean();
        echo "Something went wrong. Please try again later.";
        exit;
    }
} else {
    ob_clean();
    echo "All input fields are required!";
    exit;
}
?>