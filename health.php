<?php
/**
 * Health Check Endpoint for Render
 */

// Set JSON response header
header('Content-Type: application/json');

// Basic health check
$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'app' => 'XD Chat App',
    'version' => '2.0.0'
];

// Optional: Check database connection if needed
try {
    if (isset($_GET['check_db']) && $_GET['check_db'] === '1') {
        require_once 'config/database.php';
        $pdo = getDatabaseConnection();
        $health['database'] = 'connected';
    }
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['status'] = 'unhealthy';
}

echo json_encode($health);
exit;
?> 