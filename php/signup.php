<?php
// User signup handler
ob_start();

require_once "../includes/init.php";

header('Content-Type: application/json');

try {
    // Already logged in?
    if (Security::isAuthenticated()) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Already logged in']);
        exit;
    }
    
    // Check CSRF
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    
    // Check required fields
    $requiredFields = ['fname', 'lname', 'email', 'password'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
            exit;
        }
    }
    
    // Clean input
    $fname = Security::sanitizeInput($_POST['fname']);
    $lname = Security::sanitizeInput($_POST['lname']);
    $email = Security::sanitizeInput($_POST['email'], 'email');
    $password = $_POST['password'];
    
    // Valid email?
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check password
    if (strlen($password) < 8) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    // Email exists?
    $stmt = $pdo->prepare("SELECT unique_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'This email is already registered']);
        exit;
    }
    
    // Validate uploaded file
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Profile image is required']);
        exit;
    }
    
    $file = $_FILES['image'];
    
    // Check file size (max 1MB)
    if ($file['size'] > 1048576) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Image size too large! Please select an image under 1MB']);
        exit;
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Please upload a valid image file (JPEG, PNG, GIF)']);
        exit;
    }
    
    // Check file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid file extension']);
        exit;
    }
    
    // Generate secure filename
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $newFileName = $timestamp . '_' . $randomString . '.' . $fileExtension;
    
    // Define upload directory
    $uploadDir = "../uploads/";
    $uploadPath = $uploadDir . $newFileName;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
        exit;
    }
    
    // Generate unique user ID
    $uniqueId = rand(time(), 100000000);
    
    // Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ]);
    
    // Insert user into database
    $sql = "INSERT INTO users (unique_id, fname, lname, email, password, img, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'Active now', CURRENT_TIMESTAMP)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $uniqueId,
        $fname,
        $lname,
        $email,
        $hashedPassword,
        $newFileName
    ]);
    
    if ($result) {
        // Set session variables
        $_SESSION['unique_id'] = $uniqueId;
        $_SESSION['authenticated'] = true;
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful!',
            'redirect' => 'users.php'
        ]);
    } else {
        // Delete uploaded file if user creation failed
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to create user account']);
    }
    
} catch (PDOException $e) {
    // Delete uploaded file if there was an error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log("Signup error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    // Delete uploaded file if there was an error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log("Signup error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}
?> 