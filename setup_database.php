<?php
// This file creates the necessary database table
// Run this file ONCE after uploading to create the table

require_once 'config.php';

$conn = getDBConnection();

// Create single leads table for all form submissions
$sql_leads = "CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(255) NOT NULL,
    form_type ENUM('hero', 'brochure', 'application') NOT NULL,
    consent TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_form_type (form_type),
    INDEX idx_created_at (created_at)
)";

// Execute query
$tables_created = [];
$errors = [];

if ($conn->query($sql_leads) === TRUE) {
    $tables_created[] = "leads (unified table for all forms)";
} else {
    $errors[] = "Error creating leads table: " . $conn->error;
}

closeDBConnection($conn);

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        ul { list-style: none; padding: 0; }
        li { padding: 5px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Database Setup Results</h1>
    
    <?php if (count($tables_created) > 0): ?>
        <div class="success">
            <h3>✓ Successfully Created Tables:</h3>
            <ul>
                <?php foreach ($tables_created as $table): ?>
                    <li>• <?php echo htmlspecialchars($table); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (count($errors) > 0): ?>
        <div class="error">
            <h3>✗ Errors:</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li>• <?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="warning">
        <strong>Important:</strong> For security reasons, delete this file (setup_database.php) after running it once.
    </div>
    
    <p><a href="index.html">← Back to Website</a></p>
</body>
</html>
