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

// Handle question deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_question'])) {
    $question_id = $_POST['question_id'];
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // First delete answers
        $sql = "DELETE FROM quiz_answers WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete the question
        $sql = "DELETE FROM quiz_questions WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "Question and its answers deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting question: " . $e->getMessage();
    }
}

// Get all questions for this quiz
$sql = "SELECT q.*, 
        (SELECT COUNT(*) FROM quiz_answers WHERE question_id = q.question_id) as answer_count
        FROM quiz_questions q 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">Quiz Questions</h3>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo htmlspecialchars($quiz['course_title']); ?></p>
                    </div>
                    <div>
                        <a href="add-question.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Question
                        </a>
                        <a href="quizzes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%">#</th>
                                    <th style="width: 50%">Question</th>
                                    <th class="text-center" style="width: 15%">Type</th>
                                    <th class="text-center" style="width: 15%">Answers</th>
                                    <th class="text-center" style="width: 15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($questions) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $count++; ?></td>
                                            <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($question['question_type']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">
                                                    <?php echo $question['answer_count']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="edit-question.php?id=<?php echo $question['question_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit Question">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="question-answers.php?id=<?php echo $question['question_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Manage Answers">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <form method="post" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this question and all its answers?');">
                                                        <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                                        <button type="submit" name="delete_question" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Delete Question">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-question-circle fa-2x mb-3"></i>
                                                <p class="mb-0">No questions found. Click the "Add Question" button to add one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}
</style>

<?php require_once '../templates/footer.php'; ?> 