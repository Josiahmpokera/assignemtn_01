<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Read SQL file
$sql = file_get_contents('create_instructors_table.sql');

// Execute SQL queries
if (mysqli_multi_query($conn, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Instructors table created successfully!";
} else {
    echo "Error creating instructors table: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 