<?php
// Database configuration
define('DB_HOST', 'localhost'); // Usually 'localhost' in cPanel
define('DB_USER', 'manipal1_samarth'); // Your cPanel database username
define('DB_PASS', '1!2@3#QwE'); // Your database password
define('DB_NAME', 'manipal1_manipal_db'); // Your database name

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
