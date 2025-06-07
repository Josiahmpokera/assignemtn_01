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

// Verify that the module belongs to a course owned by the instructor
$sql = "SELECT m.*, c.instructor_id 
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

// Handle lesson creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']);
    $duration = trim($_POST['duration']);
    $order_number = trim($_POST['order_number']);
    
    if (empty($title) || empty($content) || empty($order_number)) {
        $error = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO lessons (module_id, title, content, video_url, duration, order_number) 
                VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssii", $module_id, $title, $content, $video_url, $duration, $order_number);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Lesson added successfully!";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get existing lessons
$lessons_sql = "SELECT * FROM lessons WHERE module_id = ? ORDER BY order_number";
$lessons = [];
if ($stmt = mysqli_prepare($conn, $lessons_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $module_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($lesson = mysqli_fetch_assoc($result)) {
            $lessons[] = $lesson;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Add Lessons to Module: <?php echo htmlspecialchars($module['title']); ?></h2>
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
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Lesson Content</label>
                            <textarea name="content" id="content" class="form-control" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Video URL (Optional)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control">
                            <small class="text-muted">Enter the URL of the video lesson (YouTube, Vimeo, etc.)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" name="duration" id="duration" class="form-control" min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Lesson</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Lessons -->
            <div class="card">
                <div class="card-header">
                    <h3>Existing Lessons</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($lessons)): ?>
                        <p>No lessons added yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($lessons as $lesson): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                                            <p class="mb-1"><?php echo substr(htmlspecialchars($lesson['content']), 0, 100) . '...'; ?></p>
                                            <small>
                                                Duration: <?php echo $lesson['duration']; ?> minutes
                                                <br>
                                                Order: <?php echo $lesson['order_number']; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="edit-lesson.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                               class="btn btn-sm btn-primary">Edit</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Module Information</h4>
                    <p><strong>Title:</strong> <?php echo htmlspecialchars($module['title']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($module['description']); ?></p>
                    <p><strong>Order:</strong> <?php echo $module['order_number']; ?></p>
                    <a href="manage-course.php?id=<?php echo $module['course_id']; ?>" class="btn btn-secondary w-100">Back to Course</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 