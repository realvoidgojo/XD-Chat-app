<?php
/**
 * Performance Monitoring Script
 * Use this to monitor application performance
 */

// Start timing
$startTime = microtime(true);
$startMemory = memory_get_usage();

// Include the main application
require_once 'includes/init.php';

// Get current user ID
$currentUserId = $_SESSION['unique_id'] ?? null;

// Include User model
require_once 'models/User.php';
$userModel = new User();

// Performance metrics
$metrics = [
    'session_time' => 0,
    'database_time' => 0,
    'total_time' => 0,
    'memory_usage' => 0,
    'database_queries' => 0
];

// Measure database performance
$dbStartTime = microtime(true);
try {
    $currentUser = $userModel->getById($currentUserId);
    $users = $userModel->getAllExcept($currentUserId);
    $metrics['database_queries'] = 2; // Approximate query count
} catch (Exception $e) {
    $currentUser = null;
    $users = [];
}
$metrics['database_time'] = microtime(true) - $dbStartTime;

// Calculate total metrics
$metrics['total_time'] = microtime(true) - $startTime;
$metrics['memory_usage'] = memory_get_usage() - $startMemory;

// Output performance data (only in development)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    header('Content-Type: application/json');
    echo json_encode($metrics, JSON_PRETTY_PRINT);
    exit;
}

// Log performance issues
if ($metrics['total_time'] > 1.0) { // More than 1 second
    error_log("Performance warning: Page load took {$metrics['total_time']} seconds");
}

if ($metrics['memory_usage'] > 10 * 1024 * 1024) { // More than 10MB
    error_log("Performance warning: Memory usage was {$metrics['memory_usage']} bytes");
}
?> 