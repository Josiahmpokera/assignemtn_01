<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $level = trim($_POST['level']);
    $price = trim($_POST['price']);
    
    // Validate input
    if (empty($title) || empty($description) || empty($category) || empty($level) || empty($price)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a positive number.";
    } else {
        // Insert course
        $sql = "INSERT INTO courses (title, description, instructor_id, category, level, price) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssisss", $title, $description, $user_id, $category, $level, $price);
            
            if (mysqli_stmt_execute($stmt)) {
                $course_id = mysqli_insert_id($conn);
                $success = "Course created successfully!";
                
                // Redirect to add modules page
                header("location: add-modules.php?id=" . $course_id);
                exit;
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Create New Course</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="courseForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Course Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea name="description" id="description" class="form-control" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">Level</label>
                            <select name="level" id="level" class="form-select" required>
                                <option value="">Select Level</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 