<?php
// Auth controller
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $user;
    
    public function __construct() {
        Security::initSecureSession();
        $this->user = new User();
    }
    
    // Handle signup
    public function signup() {
        header('Content-Type: application/json');
        
        try {
            // Already logged in?
            if (Security::isAuthenticated()) {
                echo json_encode(['success' => false, 'error' => 'Already logged in']);
                return;
            }
            
            // Check CSRF
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid security token']);
                return;
            }
            
            // Check required fields
            $requiredFields = ['fname', 'lname', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
                    return;
                }
            }
            
            // Clean input
            $data = [
                'fname' => Security::sanitizeInput($_POST['fname']),
                'lname' => Security::sanitizeInput($_POST['lname']),
                'email' => Security::sanitizeInput($_POST['email'], 'email'),
                'password' => $_POST['password'] // Don't sanitize password
            ];
            
            // Check file upload
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'Profile image is required']);
                return;
            }
            
            // Validate file
            $fileErrors = Security::validateUploadedFile($_FILES['image']);
            if (!empty($fileErrors)) {
                echo json_encode(['success' => false, 'error' => implode(', ', $fileErrors)]);
                return;
            }
            
            // Save file
            $newFilename = Security::generateSecureFilename($_FILES['image']['name']);
            $uploadPath = UPLOAD_DIR . $newFilename;
            
            // Create upload dir
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
                return;
            }
            
            $data['img'] = $newFilename;
            
            // Create user
            $result = $this->user->create($data);
            
            if ($result['success']) {
                // Set session
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['authenticated'] = true;
                
                // Refresh session
                session_regenerate_id(true);
                
                echo json_encode(['success' => true, 'redirect' => 'users.php']);
            } else {
                // Clean up file
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                
                echo json_encode([
                    'success' => false, 
                    'error' => implode(', ', $result['errors'])
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Signup error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
        }
    }
    
    // Handle login
    public function login() {
        header('Content-Type: application/json');
        
        try {
            // Already logged in?
            if (Security::isAuthenticated()) {
                echo json_encode(['success' => false, 'error' => 'Already logged in']);
                return;
            }
            
            // Check CSRF
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid security token']);
                return;
            }
            
            // Check fields
            if (!isset($_POST['email']) || !isset($_POST['password']) || 
                empty(trim($_POST['email'])) || empty(trim($_POST['password']))) {
                echo json_encode(['success' => false, 'error' => 'Email and password are required']);
                return;
            }
            
            $email = Security::sanitizeInput($_POST['email'], 'email');
            $password = $_POST['password'];
            
            // Valid email?
            if (!Security::validateInput($email, 'email')) {
                echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                return;
            }
            
            // Try login
            $result = $this->user->authenticate($email, $password);
            
            if ($result['success']) {
                // Set session
                $_SESSION['user_id'] = $result['user']['unique_id'];
                $_SESSION['authenticated'] = true;
                
                // Set online status
                $this->user->updateStatus($result['user']['unique_id'], 'Active now');
                
                // Refresh session
                session_regenerate_id(true);
                
                echo json_encode(['success' => true, 'redirect' => 'users.php']);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
        }
    }
    
    // Handle logout
    public function logout() {
        try {
            if (Security::isAuthenticated()) {
                // Set offline status
                $this->user->updateStatus($_SESSION['user_id'], 'Offline now');
            }
            
            // Clear session
            Security::logout();
            
            // Back to login
            header('Location: login.php');
            exit;
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            header('Location: login.php');
            exit;
        }
    }
    
    // Check auth status
    public function checkAuth() {
        header('Content-Type: application/json');
        
        echo json_encode([
            'authenticated' => Security::isAuthenticated(),
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'signup':
            $controller->signup();
            break;
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'check':
            $controller->checkAuth();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?> 