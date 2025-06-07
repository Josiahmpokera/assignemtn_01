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

$error = '';
$success = '';

// Get quiz ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: quizzes.php");
    exit;
}

$quiz_id = $_GET['id'];

// Get all courses and their lessons
$sql = "SELECT c.course_id, c.title as course_title, 
        l.lesson_id, l.title as lesson_title
        FROM courses c
        JOIN modules m ON c.course_id = m.course_id
        JOIN lessons l ON m.module_id = l.module_id
        ORDER BY c.title, l.title";

$result = mysqli_query($conn, $sql);
$courses = [];
$lessons = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!isset($courses[$row['course_id']])) {
            $courses[$row['course_id']] = [
                'title' => $row['course_title'],
                'lessons' => []
            ];
        }
        $courses[$row['course_id']]['lessons'][$row['lesson_id']] = $row['lesson_title'];
    }
}

// Get quiz details
$sql = "SELECT q.*, l.module_id, m.course_id 
        FROM quizzes q 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        WHERE q.quiz_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("location: quizzes.php");
    exit;
}

$quiz = mysqli_fetch_assoc($result);
$current_course_id = $quiz['course_id'];
$current_lesson_id = $quiz['lesson_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $lesson_id = trim($_POST['lesson_id']);
    $passing_score = trim($_POST['passing_score']);
    
    // Validate input
    if (empty($title) || empty($lesson_id) || empty($passing_score)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($passing_score) || $passing_score < 0 || $passing_score > 100) {
        $error = "Passing score must be a number between 0 and 100.";
    } else {
        // Update quiz
        $sql = "UPDATE quizzes SET lesson_id = ?, title = ?, description = ?, passing_score = ? WHERE quiz_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issii", $lesson_id, $title, $description, $passing_score, $quiz_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Quiz updated successfully!";
                // Refresh quiz data
                $quiz['title'] = $title;
                $quiz['description'] = $description;
                $quiz['lesson_id'] = $lesson_id;
                $quiz['passing_score'] = $passing_score;
            } else {
                $error = "Error updating quiz: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Edit Quiz</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="quizForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($quiz['title']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select name="course_id" id="course_id" class="form-select" required>
                                <option value="">Select a Course</option>
                                <?php foreach ($courses as $course_id => $course): ?>
                                    <option value="<?php echo $course_id; ?>" <?php echo $course_id == $current_course_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="lesson_id" class="form-label">Lesson <span class="text-danger">*</span></label>
                            <select name="lesson_id" id="lesson_id" class="form-select" required>
                                <option value="">Select a Lesson</option>
                                <?php foreach ($courses[$current_course_id]['lessons'] as $lesson_id => $lesson_title): ?>
                                    <option value="<?php echo $lesson_id; ?>" <?php echo $lesson_id == $current_lesson_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lesson_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                            <input type="number" name="passing_score" id="passing_score" class="form-control" 
                                   min="0" max="100" required 
                                   value="<?php echo htmlspecialchars($quiz['passing_score']); ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Quiz</button>
                            <a href="quizzes.php" class="btn btn-secondary">Back to Quizzes</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('course_id').addEventListener('change', function() {
    const courseId = this.value;
    const lessonSelect = document.getElementById('lesson_id');
    lessonSelect.innerHTML = '<option value="">Select a Lesson</option>';
    
    if (courseId) {
        const lessons = <?php echo json_encode($courses); ?>[courseId].lessons;
        for (const [lessonId, lessonTitle] of Object.entries(lessons)) {
            const option = document.createElement('option');
            option.value = lessonId;
            option.textContent = lessonTitle;
            lessonSelect.appendChild(option);
        }
    }
});
</script>

<?php require_once '../templates/footer.php'; ?> 