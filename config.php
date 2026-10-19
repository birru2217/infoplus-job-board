<?php
/**
 * Database Configuration
 * Update these details as per your localhost settings.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'infoplus_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If connection fails, we define PDO as null so our files can fall back to beautiful Mock arrays
    $pdo = null;
}
// =================================================================
// GOOGLE & LINKEDIN OAUTH CONFIGURATION (FOR LIVE HOSTING & LOCALHOST)
// =================================================================
// Gaafa Free Host irratti upload gootan keys kanneen galchitu
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID'); // Replace on Live Host
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET'); // Replace on Live Host

// Redirect URI (Automatically detects HTTP or HTTPS on Live Host)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
define('GOOGLE_REDIRECT_URI', $host_url . '/google-callback.php');
?>
