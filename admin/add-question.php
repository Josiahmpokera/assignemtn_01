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

// Get quiz details
$sql = "SELECT q.*, c.title as course_title, l.title as lesson_title
        FROM quizzes q 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
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
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert question
            $sql = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, points) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issi", $quiz_id, $question_text, $question_type, $points);
            mysqli_stmt_execute($stmt);
            $question_id = mysqli_insert_id($conn);
            
            // If multiple choice, insert answers
            if ($question_type == 'multiple_choice') {
                $answers = $_POST['answers'];
                $correct_answer = $_POST['correct_answer'];
                
                foreach ($answers as $index => $answer_text) {
                    if (!empty($answer_text)) {
                        $is_correct = ($index == $correct_answer) ? 1 : 0;
                        $sql = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "isi", $question_id, $answer_text, $is_correct);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            $success = "Question and answers added successfully!";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error adding question: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Add New Question</h3>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo htmlspecialchars($quiz['course_title']); ?></p>
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
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required><?php echo isset($question_text) ? htmlspecialchars($question_text) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                            <select name="question_type" id="question_type" class="form-select" required onchange="toggleAnswerFields()">
                                <option value="">Select Question Type</option>
                                <option value="multiple_choice" <?php echo (isset($question_type) && $question_type == 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="true_false" <?php echo (isset($question_type) && $question_type == 'true_false') ? 'selected' : ''; ?>>True/False</option>
                                <option value="short_answer" <?php echo (isset($question_type) && $question_type == 'short_answer') ? 'selected' : ''; ?>>Short Answer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                            <input type="number" name="points" id="points" class="form-control" 
                                   min="1" required 
                                   value="<?php echo isset($points) ? htmlspecialchars($points) : '1'; ?>">
                        </div>
                        
                        <!-- Answer fields for multiple choice -->
                        <div id="answerFields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Answers <span class="text-danger">*</span></label>
                                <div id="answersContainer">
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="correct_answer" value="<?php echo $i; ?>" 
                                                       class="form-check-input mt-0" required>
                                            </div>
                                            <input type="text" name="answers[]" class="form-control" 
                                                   placeholder="Enter answer option <?php echo $i + 1; ?>">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add Question</button>
                            <a href="quiz-questions.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Back to Questions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAnswerFields() {
    const questionType = document.getElementById('question_type').value;
    const answerFields = document.getElementById('answerFields');
    
    if (questionType === 'multiple_choice') {
        answerFields.style.display = 'block';
        // Make answer fields required
        document.querySelectorAll('input[name="answers[]"]').forEach(input => {
            input.required = true;
        });
    } else {
        answerFields.style.display = 'none';
        // Remove required attribute
        document.querySelectorAll('input[name="answers[]"]').forEach(input => {
            input.required = false;
        });
    }
}

// Initialize answer fields visibility
document.addEventListener('DOMContentLoaded', function() {
    toggleAnswerFields();
});
</script>

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

.input-group-text {
    background-color: #f8f9fa;
}
</style>

<?php require_once '../templates/footer.php'; ?> 