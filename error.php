<?php
/**
 * Error Page Handler
 * Handles 404, 403, and 500 errors
 */

$errorCode = $_GET['code'] ?? '404';
$errorMessage = $_GET['message'] ?? 'Page not found';

switch ($errorCode) {
    case '403':
        $title = 'Access Forbidden';
        $message = 'You do not have permission to access this resource.';
        break;
    case '500':
        $title = 'Server Error';
        $message = 'An internal server error occurred. Please try again later.';
        break;
    default:
        $title = 'Page Not Found';
        $message = 'The page you are looking for could not be found.';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - XD Chat App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: white;
        }
        .error-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .error-title {
            font-size: 24px;
            margin: 20px 0;
        }
        .error-message {
            font-size: 16px;
            margin: 20px 0;
            opacity: 0.9;
        }
        .back-btn {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 25px;
            margin-top: 20px;
            transition: background 0.3s ease;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code"><?php echo $errorCode; ?></h1>
        <h2 class="error-title"><?php echo $title; ?></h2>
        <p class="error-message"><?php echo $message; ?></p>
        <a href="/" class="back-btn">Go Home</a>
    </div>
</body>
</html> 