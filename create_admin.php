<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// The password you want to use for admin
$password = "admin123"; // Change this to your desired password

// Generate the hashed password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Display the results
echo "<h2>Admin Password Generator</h2>";
echo "<p>Your plain password: <strong>" . htmlspecialchars($password) . "</strong></p>";
echo "<p>Your hashed password: <strong>" . htmlspecialchars($hashed_password) . "</strong></p>";

// Generate the SQL query
$sql = "INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '" . $hashed_password . "', 'admin@example.com', 'System Administrator', 'admin');";

echo "<h3>SQL Query to create admin account:</h3>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Copy the hashed password above</li>";
echo "<li>Go to phpMyAdmin</li>";
echo "<li>Select your database (online_learning)</li>";
echo "<li>Go to the SQL tab</li>";
echo "<li>Paste and modify the SQL query above with your desired username, email, and full name</li>";
echo "<li>Execute the query</li>";
echo "</ol>";
?> 