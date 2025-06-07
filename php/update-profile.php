<?php
/**
 * Update User Profile
 * XD Chat App
 */

// Start output buffering to prevent any unexpected output
ob_start();

/**
 * Handle image upload
 */
function handleImageUpload($file) {
    $uploadDir = __DIR__ . '/../uploads/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Remove old image file
 */
function removeOldImage($imageName) {
    if (!empty($imageName) && $imageName !== 'default-avatar.png') {
        $uploadDir = __DIR__ . '/../uploads/';
        $filepath = $uploadDir . $imageName;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

try {
    // Include initialization
    require_once __DIR__ . "/../includes/init.php";
    
    // Clear any previous output
    ob_clean();
    
    // Set content type for JSON response
    header('Content-Type: application/json');

    // Check authentication
    if (!Security::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Check CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $userId = $_SESSION['unique_id'];
    $fname = Security::sanitizeInput($_POST['fname'] ?? '');
    $lname = Security::sanitizeInput($_POST['lname'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $status = Security::sanitizeInput($_POST['status'] ?? 'Active now');

    // Validate inputs
    if (empty($fname) || empty($lname) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    // Check if email already exists for another user
    $stmt = $pdo->prepare("SELECT unique_id FROM users WHERE email = ? AND unique_id != ?");
    $stmt->execute([$email, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
    
    // Get current user data to retrieve old image
    $stmt = $pdo->prepare("SELECT img FROM users WHERE unique_id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldImage = $currentUser['img'] ?? null;
    
    // Handle image upload if provided
    $imagePath = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = handleImageUpload($_FILES['profile_image']);
        if (!$imagePath) {
            echo json_encode(['success' => false, 'error' => 'Image upload failed']);
            exit;
        }
        
        // Remove old image if upload was successful and we have a new image
        if ($oldImage) {
            removeOldImage($oldImage);
        }
    }
    
    // Update user profile
    if ($imagePath) {
        $stmt = $pdo->prepare("UPDATE users SET fname = ?, lname = ?, email = ?, status = ?, img = ? WHERE unique_id = ?");
        $result = $stmt->execute([$fname, $lname, $email, $status, $imagePath, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fname = ?, lname = ?, email = ?, status = ? WHERE unique_id = ?");
        $result = $stmt->execute([$fname, $lname, $email, $status, $userId]);
    }
    
    if ($result) {
        // Update session data
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['email'] = $email;
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'status' => $status,
                'image' => $imagePath ?: $oldImage
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
    
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    // Clear any output and return error as JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

// End output buffering and send
ob_end_flush();
?> 