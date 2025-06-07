<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Read and execute the SQL file
$sql = file_get_contents('create_lesson_completion_table.sql');

if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Lesson completion table created successfully!";
} else {
    echo "Error creating lesson completion table: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 