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

// Check if module ID is provided
if (!isset($_GET['module_id'])) {
    header("location: courses.php");
    exit;
}

$module_id = $_GET['module_id'];
$error = '';
$success = '';

// Get module and course details
$sql = "SELECT m.*, c.course_id, c.title as course_title 
        FROM modules m 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE m.module_id = ?";
$module = null;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $module_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $module = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$module) {
    header("location: courses.php");
    exit;
}

// Handle lesson creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']);
    $duration = trim($_POST['duration']);
    $order_number = trim($_POST['order_number']);
    
    // Validate input
    if (empty($title) || empty($content) || empty($order_number)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($order_number) || $order_number < 1) {
        $error = "Order number must be a positive number.";
    } elseif (!empty($duration) && (!is_numeric($duration) || $duration < 0)) {
        $error = "Duration must be a positive number.";
    } else {
        $sql = "INSERT INTO lessons (module_id, title, content, video_url, duration, order_number) 
                VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssii", $module_id, $title, $content, $video_url, $duration, $order_number);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Lesson added successfully!";
                // Clear form
                $title = $content = $video_url = $duration = $order_number = '';
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
                    <h2>Add New Lesson to: <?php echo htmlspecialchars($module['title']); ?></h2>
                    <p class="text-muted mb-0">Course: <?php echo htmlspecialchars($module['course_title']); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="lessonForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Lesson Title</label>
                            <input type="text" name="title" id="title" class="form-control" required 
                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Lesson Content</label>
                            <textarea name="content" id="content" class="form-control" rows="10" required><?php 
                                echo isset($content) ? htmlspecialchars($content) : ''; 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Video URL (Optional)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control" 
                                   value="<?php echo isset($video_url) ? htmlspecialchars($video_url) : ''; ?>">
                            <small class="text-muted">Enter the URL of the video for this lesson</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes, Optional)</label>
                            <input type="number" name="duration" id="duration" class="form-control" min="0" 
                                   value="<?php echo isset($duration) ? htmlspecialchars($duration) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required 
                                   value="<?php echo isset($order_number) ? htmlspecialchars($order_number) : ''; ?>">
                            <small class="text-muted">This determines the order in which lessons appear in the module</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add Lesson</button>
                            <a href="module-lessons.php?id=<?php echo $module_id; ?>" class="btn btn-secondary">Back to Lessons</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 