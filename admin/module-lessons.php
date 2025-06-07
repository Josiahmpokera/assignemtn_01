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
if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$module_id = $_GET['id'];
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

// Handle lesson deletion
if (isset($_POST['delete_lesson'])) {
    $lesson_id = $_POST['lesson_id'];
    $sql = "DELETE FROM lessons WHERE lesson_id = ? AND module_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $lesson_id, $module_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Lesson deleted successfully!";
        } else {
            $error = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all lessons for this module
$lessons = [];
$sql = "SELECT * FROM lessons WHERE module_id = ? ORDER BY order_number ASC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $module_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $lessons[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Module Lessons: <?php echo htmlspecialchars($module['title']); ?></h2>
            <p class="text-muted">Course: <?php echo htmlspecialchars($module['course_title']); ?></p>
            <a href="add-lesson.php?module_id=<?php echo $module_id; ?>" class="btn btn-primary">Add New Lesson</a>
            <a href="course-modules.php?id=<?php echo $module['course_id']; ?>" class="btn btn-secondary">Back to Modules</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Video</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lessons)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No lessons found for this module.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lessons as $lesson): ?>
                                        <tr>
                                            <td><?php echo $lesson['order_number']; ?></td>
                                            <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                            <td><?php echo $lesson['duration'] ? $lesson['duration'] . ' minutes' : 'Not specified'; ?></td>
                                            <td>
                                                <?php if ($lesson['video_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($lesson['video_url']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                        View Video
                                                    </a>
                                                <?php else: ?>
                                                    No video
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit-lesson.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="lesson_id" value="<?php echo $lesson['lesson_id']; ?>">
                                                    <button type="submit" name="delete_lesson" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this lesson?');">
                                                        Delete
                                                    </button>
                                                </form>
                                                <a href="manage-quiz.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" 
                                                   class="btn btn-sm btn-info">Quiz</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 