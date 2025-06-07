<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if lesson ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$lesson_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get lesson details and verify ownership
$sql = "SELECT l.*, m.module_id, m.course_id, c.instructor_id 
        FROM lessons l 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE l.lesson_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $lesson_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $lesson = mysqli_fetch_assoc($result);
        if (!$lesson) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle lesson update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']);
    $duration = trim($_POST['duration']);
    $order_number = trim($_POST['order_number']);
    
    if (empty($title) || empty($content) || empty($order_number)) {
        $error = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE lessons 
                SET title = ?, content = ?, video_url = ?, duration = ?, order_number = ? 
                WHERE lesson_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssiii", $title, $content, $video_url, $duration, $order_number, $lesson_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Lesson updated successfully!";
                // Update the lesson data for display
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
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Edit Lesson</h2>
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
                            <label for="title" class="form-label">Lesson Title</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Lesson Content</label>
                            <textarea name="content" id="content" class="form-control" rows="5" required><?php 
                                echo htmlspecialchars($lesson['content']); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Video URL (Optional)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control"
                                   value="<?php echo htmlspecialchars($lesson['video_url']); ?>">
                            <small class="text-muted">Enter the URL of the video lesson (YouTube, Vimeo, etc.)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" name="duration" id="duration" class="form-control" min="1"
                                   value="<?php echo $lesson['duration']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required
                                   value="<?php echo $lesson['order_number']; ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Lesson</button>
                            <a href="add-lessons.php?id=<?php echo $lesson['module_id']; ?>" 
                               class="btn btn-secondary">Back to Lessons</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Lesson Information</h4>
                    <p><strong>Module:</strong> <?php echo htmlspecialchars($lesson['module_id']); ?></p>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($lesson['course_id']); ?></p>
                    <p><strong>Created:</strong> <?php echo date('F j, Y', strtotime($lesson['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($lesson['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 