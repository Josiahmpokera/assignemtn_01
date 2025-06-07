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

// Check if lesson ID is provided
if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$lesson_id = $_GET['id'];
$error = '';
$success = '';

// Get lesson details
$sql = "SELECT l.*, m.module_id, m.title as module_title, c.course_id, c.title as course_title 
        FROM lessons l 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE l.lesson_id = ?";
$lesson = null;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $lesson_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $lesson = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$lesson) {
    header("location: courses.php");
    exit;
}

// Handle lesson update
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
        $sql = "UPDATE lessons SET title = ?, content = ?, video_url = ?, duration = ?, order_number = ? 
                WHERE lesson_id = ? AND module_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssiii", $title, $content, $video_url, $duration, $order_number, $lesson_id, $lesson['module_id']);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Lesson updated successfully!";
                // Update lesson data
                $lesson['title'] = $title;
                $lesson['content'] = $content;
                $lesson['video_url'] = $video_url;
                $lesson['duration'] = $duration;
                $lesson['order_number'] = $order_number;
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
                    <h2>Edit Lesson: <?php echo htmlspecialchars($lesson['title']); ?></h2>
                    <p class="text-muted mb-0">
                        Course: <?php echo htmlspecialchars($lesson['course_title']); ?><br>
                        Module: <?php echo htmlspecialchars($lesson['module_title']); ?>
                    </p>
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
                                   value="<?php echo htmlspecialchars($lesson['title']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Lesson Content</label>
                            <textarea name="content" id="content" class="form-control" rows="10" required><?php 
                                echo htmlspecialchars($lesson['content']); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Video URL (Optional)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($lesson['video_url']); ?>">
                            <small class="text-muted">Enter the URL of the video for this lesson</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes, Optional)</label>
                            <input type="number" name="duration" id="duration" class="form-control" min="0" 
                                   value="<?php echo htmlspecialchars($lesson['duration']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required 
                                   value="<?php echo htmlspecialchars($lesson['order_number']); ?>">
                            <small class="text-muted">This determines the order in which lessons appear in the module</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Lesson</button>
                            <a href="module-lessons.php?id=<?php echo $lesson['module_id']; ?>" class="btn btn-secondary">Back to Lessons</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 