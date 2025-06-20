<?php
/**
 * Signup API Endpoint
 * XD Chat App
 */

require_once __DIR__ . '/../controllers/AuthController.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $controller = new AuthController();
    $controller->signup();
} catch (Exception $e) {
    error_log("Signup API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?> 