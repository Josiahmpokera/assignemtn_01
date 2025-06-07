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
if (!isset($_GET['question_id']) || !is_numeric($_GET['question_id'])) {
    header("location: quizzes.php");
    exit;
}

$question_id = $_GET['question_id'];

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
    $answer_text = trim($_POST['answer_text']);
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;
    
    // Validate input
    if (empty($answer_text)) {
        $error = "Please enter the answer text.";
    } else {
        // Insert answer
        $sql = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "isi", $question_id, $answer_text, $is_correct);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Answer added successfully!";
                // Clear form
                $answer_text = '';
                $is_correct = 0;
            } else {
                $error = "Error adding answer: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get existing answers
$sql = "SELECT * FROM quiz_answers WHERE question_id = ? ORDER BY answer_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $question_id);
mysqli_stmt_execute($stmt);
$answers = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Add Answer</h3>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($question['quiz_title']); ?> - <?php echo htmlspecialchars($question['course_title']); ?></p>
                    <p class="text-muted mb-0">Question: <?php echo htmlspecialchars($question['question_text']); ?></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="answerForm">
                        <div class="mb-3">
                            <label for="answer_text" class="form-label">Answer Text <span class="text-danger">*</span></label>
                            <textarea name="answer_text" id="answer_text" class="form-control" rows="2" required><?php echo isset($answer_text) ? htmlspecialchars($answer_text) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_correct" id="is_correct" class="form-check-input" 
                                       <?php echo isset($is_correct) && $is_correct ? 'checked' : ''; ?>>
                                <label for="is_correct" class="form-check-label">This is the correct answer</label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add Answer</button>
                            <a href="quiz-questions.php?id=<?php echo $question['quiz_id']; ?>" class="btn btn-secondary">Back to Questions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Existing Answers</h4>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($answers) > 0): ?>
                        <div class="list-group">
                            <?php while ($answer = mysqli_fetch_assoc($answers)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                            <?php if ($answer['is_correct']): ?>
                                                <span class="badge bg-success ms-2">Correct</span>
                                            <?php endif; ?>
                                        </div>
                                        <form method="post" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this answer?');">
                                            <input type="hidden" name="delete_answer" value="<?php echo $answer['answer_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No answers added yet.</p>
                    <?php endif; ?>
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

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.badge {
    font-weight: 500;
}
</style>

<?php require_once '../templates/footer.php'; ?> 