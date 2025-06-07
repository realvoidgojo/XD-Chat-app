<?php
/**
 * Get Chat Messages API Endpoint
 * XD Chat App
 */

require_once __DIR__ . '/../controllers/ChatController.php';

// Set headers
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<div class="text">Method not allowed</div>';
    exit;
}

try {
    $controller = new ChatController();
    $controller->getMessages();
} catch (Exception $e) {
    error_log("Get chat API error: " . $e->getMessage());
    echo '<div class="text">Error loading messages</div>';
}
?> 