<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if module ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$module_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get module details and verify ownership
$sql = "SELECT m.*, c.course_id, c.instructor_id 
        FROM modules m 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE m.module_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $module_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $module = mysqli_fetch_assoc($result);
        if (!$module) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle module update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $order_number = trim($_POST['order_number']);
    
    if (empty($title) || empty($description) || empty($order_number)) {
        $error = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE modules 
                SET title = ?, description = ?, order_number = ? 
                WHERE module_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $title, $description, $order_number, $module_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Module updated successfully!";
                // Update the module data for display
                $module['title'] = $title;
                $module['description'] = $description;
                $module['order_number'] = $order_number;
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Edit Module</h2>
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
                            <label for="title" class="form-label">Module Title</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($module['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Module Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" required><?php 
                                echo htmlspecialchars($module['description']); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required
                                   value="<?php echo $module['order_number']; ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Module</button>
                            <a href="manage-course.php?id=<?php echo $module['course_id']; ?>" 
                               class="btn btn-secondary">Back to Course</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Module Information</h4>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($module['course_id']); ?></p>
                    <p><strong>Created:</strong> <?php echo date('F j, Y', strtotime($module['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($module['updated_at'])); ?></p>
                    <a href="add-lessons.php?id=<?php echo $module_id; ?>" class="btn btn-success w-100">Add Lessons</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 