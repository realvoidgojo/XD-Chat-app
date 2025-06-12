<?php
/**
 * Supabase Configuration for XD Chat App
 * Environment-specific database configuration
 */

// Load environment variables if available (for local development)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Supabase Database Configuration
// These can be set as environment variables in production (Render)
define("SUPABASE_HOST", $_ENV['SUPABASE_HOST'] ?? getenv('SUPABASE_HOST') ?? "db.your-project-ref.supabase.co");
define("SUPABASE_PORT", $_ENV['SUPABASE_PORT'] ?? getenv('SUPABASE_PORT') ?? "5432");
define("SUPABASE_USERNAME", $_ENV['SUPABASE_USERNAME'] ?? getenv('SUPABASE_USERNAME') ?? "postgres");
define("SUPABASE_PASSWORD", $_ENV['SUPABASE_PASSWORD'] ?? getenv('SUPABASE_PASSWORD') ?? "your-password");
define("SUPABASE_DATABASE", $_ENV['SUPABASE_DATABASE'] ?? getenv('SUPABASE_DATABASE') ?? "postgres");

// Update the main database configuration
if (!defined('DB_HOST')) {
    define("DB_HOST", SUPABASE_HOST);
    define("DB_PORT", SUPABASE_PORT);
    define("DB_USERNAME", SUPABASE_USERNAME);
    define("DB_PASSWORD", SUPABASE_PASSWORD);
    define("DB_NAME", SUPABASE_DATABASE);
}

// Supabase specific settings
define("SUPABASE_URL", $_ENV['SUPABASE_URL'] ?? "https://your-project-ref.supabase.co");
define("SUPABASE_ANON_KEY", $_ENV['SUPABASE_ANON_KEY'] ?? "your-anon-key");
define("SUPABASE_SERVICE_ROLE_KEY", $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? "your-service-role-key");

// SSL Configuration for Supabase
define("DB_SSL_MODE", "require");
?> 