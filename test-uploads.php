<?php
/**
 * Test script to verify uploads directory access
 */

// Test if uploads directory exists and is accessible
$uploadsDir = __DIR__ . '/uploads';
$defaultAvatar = $uploadsDir . '/default-avatar.png';

echo "<h1>Uploads Directory Test</h1>";

// Check if uploads directory exists
if (is_dir($uploadsDir)) {
    echo "<p>✅ Uploads directory exists</p>";
    
    // Check permissions
    $perms = substr(sprintf('%o', fileperms($uploadsDir)), -4);
    echo "<p>📁 Directory permissions: $perms</p>";
    
    // Check if default avatar exists
    if (file_exists($defaultAvatar)) {
        echo "<p>✅ Default avatar exists</p>";
        
        // Check file size
        $size = filesize($defaultAvatar);
        echo "<p>📄 File size: $size bytes</p>";
        
        // Test image display
        echo "<h2>Test Image Display:</h2>";
        echo "<img src='uploads/default-avatar.png' alt='Test Image' style='border: 2px solid #ccc; max-width: 200px;'>";
        
    } else {
        echo "<p>❌ Default avatar not found</p>";
    }
    
    // List files in uploads directory
    echo "<h2>Files in uploads directory:</h2>";
    $files = scandir($uploadsDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadsDir . '/' . $file;
            $fileSize = filesize($filePath);
            $filePerms = substr(sprintf('%o', fileperms($filePath)), -4);
            echo "<li>$file (Size: $fileSize bytes, Perms: $filePerms)</li>";
        }
    }
    echo "</ul>";
    
} else {
    echo "<p>❌ Uploads directory not found</p>";
}

// Test Apache configuration
echo "<h2>Apache Configuration Test:</h2>";
echo "<p>Testing direct access to default avatar...</p>";
echo "<p>If you can see the image below, the configuration is working:</p>";
echo "<img src='uploads/default-avatar.png' alt='Direct Access Test' style='border: 2px solid #333; max-width: 150px;'>";

// Test with absolute path
echo "<h2>Absolute Path Test:</h2>";
echo "<p>Testing with absolute path: /var/www/html/uploads/default-avatar.png</p>";
echo "<p>Note: This should show a 404 if the path is incorrect</p>";

?> 