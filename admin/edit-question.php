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

// Get question ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: quizzes.php");
    exit;
}

$question_id = $_GET['id'];

// Get question details
$sql = "SELECT q.*, qz.quiz_id, qz.title as quiz_title, c.title as course_title
        FROM quiz_questions q 
        JOIN quizzes qz ON q.quiz_id = qz.quiz_id
        JOIN lessons l ON qz.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE q.question_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $question_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("location: quizzes.php");
    exit;
}

$question = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);
    $points = trim($_POST['points']);
    
    // Validate input
    if (empty($question_text) || empty($question_type) || empty($points)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($points) || $points < 0) {
        $error = "Points must be a positive number.";
    } else {
        // Update question
        $sql = "UPDATE quiz_questions SET question_text = ?, question_type = ?, points = ? WHERE question_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $question_text, $question_type, $points, $question_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Question updated successfully!";
                // Refresh question data
                $question['question_text'] = $question_text;
                $question['question_type'] = $question_type;
                $question['points'] = $points;
            } else {
                $error = "Error updating question: " . mysqli_stmt_error($stmt);
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
                    <h3 class="mb-0">Edit Question</h3>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($question['quiz_title']); ?> - <?php echo htmlspecialchars($question['course_title']); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="questionForm">
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                            <select name="question_type" id="question_type" class="form-select" required>
                                <option value="">Select Question Type</option>
                                <option value="multiple_choice" <?php echo $question['question_type'] == 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="true_false" <?php echo $question['question_type'] == 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                <option value="short_answer" <?php echo $question['question_type'] == 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                            <input type="number" name="points" id="points" class="form-control" 
                                   min="1" required 
                                   value="<?php echo htmlspecialchars($question['points']); ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Question</button>
                            <a href="quiz-questions.php?id=<?php echo $question['quiz_id']; ?>" class="btn btn-secondary">Back to Questions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-label {
    font-weight: 500;
}

textarea.form-control {
    min-height: 100px;
}
</style>

<?php require_once '../templates/footer.php'; ?> 