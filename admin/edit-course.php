<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once 'header.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Check if course ID is provided
if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$course_id = $_GET['id'];
$error = '';
$success = '';

// Get course details
$sql = "SELECT c.*, u.full_name as instructor_name 
        FROM courses c 
        LEFT JOIN users u ON c.instructor_id = u.user_id 
        WHERE c.course_id = ?";

$course = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

if (!$course) {
    header("location: courses.php");
    exit;
}

// Get all instructors
$instructors = [];
$sql = "SELECT user_id, full_name FROM users WHERE role = 'instructor'";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $instructors[] = $row;
    }
}

// Handle course update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $level = trim($_POST['level']);
    $price = trim($_POST['price']);
    $instructor_id = trim($_POST['instructor_id']);
    $status = trim($_POST['status']);
    
    // Validate input
    if (empty($title) || empty($description) || empty($category) || empty($level) || empty($price) || empty($instructor_id)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a positive number.";
    } else {
        $sql = "UPDATE courses SET title = ?, description = ?, category = ?, level = ?, price = ?, instructor_id = ?, status = ? 
                WHERE course_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssdissi", $title, $description, $category, $level, $price, $instructor_id, $status, $course_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Course updated successfully!";
                // Update course data
                $course['title'] = $title;
                $course['description'] = $description;
                $course['category'] = $category;
                $course['level'] = $level;
                $course['price'] = $price;
                $course['instructor_id'] = $instructor_id;
                $course['status'] = $status;
            } else {
                $error = "Error updating course: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error preparing statement: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Edit Course: <?php echo htmlspecialchars($course['title']); ?></h2>
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
                            <input type="text" name="title" id="title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($course['title']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea name="description" id="description" class="form-control" rows="5" required><?php 
                                echo htmlspecialchars($course['description']); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" required 
                                   value="<?php echo htmlspecialchars($course['category']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">Level</label>
                            <select name="level" id="level" class="form-select" required>
                                <option value="beginner" <?php echo $course['level'] == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo $course['level'] == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $course['level'] == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (TZS)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required 
                                   value="<?php echo htmlspecialchars($course['price']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="instructor_id" class="form-label">Instructor</label>
                            <select name="instructor_id" id="instructor_id" class="form-select" required>
                                <option value="">Select Instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['user_id']; ?>" 
                                            <?php echo $instructor['user_id'] == $course['instructor_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active" <?php echo $course['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $course['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Course</button>
                            <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 