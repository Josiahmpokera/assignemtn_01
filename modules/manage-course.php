<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if course ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$course_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Verify that the course belongs to the instructor
$sql = "SELECT * FROM courses WHERE course_id = ? AND instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $course_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);
        if (!$course) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle course update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $level = trim($_POST['level']);
    $price = trim($_POST['price']);
    
    if (empty($title) || empty($description) || empty($category) || empty($level) || empty($price)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a positive number.";
    } else {
        $sql = "UPDATE courses SET title = ?, description = ?, category = ?, level = ?, price = ? 
                WHERE course_id = ? AND instructor_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssii", $title, $description, $category, $level, $price, $course_id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Course updated successfully!";
                // Refresh course data
                $course['title'] = $title;
                $course['description'] = $description;
                $course['category'] = $category;
                $course['level'] = $level;
                $course['price'] = $price;
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get course modules
$modules_sql = "SELECT * FROM modules WHERE course_id = ? ORDER BY order_number";
$modules = [];
if ($stmt = mysqli_prepare($conn, $modules_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($module = mysqli_fetch_assoc($result)) {
            $modules[] = $module;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get enrolled students
$enrollments_sql = "SELECT u.user_id, u.full_name, u.email, e.enrollment_date, e.status, e.progress 
                   FROM enrollments e 
                   JOIN users u ON e.student_id = u.user_id 
                   WHERE e.course_id = ?";
$enrollments = [];
if ($stmt = mysqli_prepare($conn, $enrollments_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($enrollment = mysqli_fetch_assoc($result)) {
            $enrollments[] = $enrollment;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle module deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_module'])) {
    $module_id = $_POST['module_id'];
    
    // First verify ownership
    $verify_sql = "SELECT m.* FROM modules m 
                   JOIN courses c ON m.course_id = c.course_id 
                   WHERE m.module_id = ? AND c.instructor_id = ?";
    if ($stmt = mysqli_prepare($conn, $verify_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $module_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_fetch_assoc($result)) {
                // Delete the module (cascade will handle lessons)
                $delete_sql = "DELETE FROM modules WHERE module_id = ?";
                if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $module_id);
                    if (mysqli_stmt_execute($delete_stmt)) {
                        $success = "Module deleted successfully!";
                        // Refresh modules list
                        $modules = array_filter($modules, function($m) use ($module_id) {
                            return $m['module_id'] != $module_id;
                        });
                    } else {
                        $error = "Something went wrong while deleting the module.";
                    }
                    mysqli_stmt_close($delete_stmt);
                }
            } else {
                $error = "You don't have permission to delete this module.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <!-- Course Details Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Course Details</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Course Title</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea name="description" id="description" class="form-control" rows="5" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" 
                                   value="<?php echo htmlspecialchars($course['category']); ?>" required>
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
                            <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" 
                                   value="<?php echo $course['price']; ?>" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_course" class="btn btn-primary">Update Course</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Course Modules -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Course Modules</h3>
                    <a href="add-modules.php?id=<?php echo $course_id; ?>" class="btn btn-primary">Add Module</a>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-info">No modules added yet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($modules as $module): ?>
                                <div class="list-group-item">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($module['title']); ?></h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($module['description']); ?></p>
                                        <small>Order: <?php echo $module['order_number']; ?></small>
                                    </div>
                                    <div class="mt-3">
                                        <div class="btn-group">
                                            <a href="add-lessons.php?id=<?php echo $module['module_id']; ?>" 
                                               class="btn btn-sm btn-success">Add Lessons</a>
                                            <a href="edit-module.php?id=<?php echo $module['module_id']; ?>" 
                                               class="btn btn-sm btn-primary">Edit Module</a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this module? This will also delete all its lessons.');">
                                                <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
                                                <button type="submit" name="delete_module" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Lessons List -->
                                    <?php
                                    $lessons_sql = "SELECT * FROM lessons WHERE module_id = ? ORDER BY order_number";
                                    if ($lessons_stmt = mysqli_prepare($conn, $lessons_sql)) {
                                        mysqli_stmt_bind_param($lessons_stmt, "i", $module['module_id']);
                                        if (mysqli_stmt_execute($lessons_stmt)) {
                                            $lessons_result = mysqli_stmt_get_result($lessons_stmt);
                                            if (mysqli_num_rows($lessons_result) > 0):
                                    ?>
                                        <div class="mt-3">
                                            <h6>Lessons:</h6>
                                            <div class="list-group">
                                                <?php while ($lesson = mysqli_fetch_assoc($lessons_result)): ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($lesson['title']); ?></h6>
                                                                <small>Duration: <?php echo $lesson['duration']; ?> minutes</small>
                                                            </div>
                                                            <div class="btn-group">
                                                                <a href="manage-quiz.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                                   class="btn btn-sm btn-info">Manage Quiz</a>
                                                                <a href="edit-lesson.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                                   class="btn btn-sm btn-primary">Edit</a>
                                                                <form method="post" class="d-inline" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this lesson?');">
                                                                    <input type="hidden" name="lesson_id" value="<?php echo $lesson['lesson_id']; ?>">
                                                                    <button type="submit" name="delete_lesson" class="btn btn-sm btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php 
                                            endif;
                                        }
                                        mysqli_stmt_close($lessons_stmt);
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Enrolled Students -->
            <div class="card">
                <div class="card-header">
                    <h3>Enrolled Students</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($enrollments)): ?>
                        <div class="alert alert-info">No students enrolled yet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($enrollments as $enrollment): ?>
                                <div class="list-group-item">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($enrollment['full_name']); ?></h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($enrollment['email']); ?></p>
                                    <small>
                                        Enrolled: <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?>
                                        <br>
                                        Progress: <?php echo $enrollment['progress']; ?>%
                                        <br>
                                        Status: <span class="badge bg-<?php echo $enrollment['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($enrollment['status']); ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 