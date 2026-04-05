<?php
// Database configuration for Hostinger
define('DB_HOST', 'localhost'); // On Hostinger shared hosting, localhost works
define('DB_USER', 'u261758575_samarth'); // Your Hostinger database username
define('DB_PASS', "s8jlXp3*Le"); // Your Hostinger database password
define('DB_NAME', 'u261758575_manipal'); // Your Hostinger database name

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    return $conn;
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
