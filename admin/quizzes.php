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

// Handle quiz deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_quiz'])) {
    $quiz_id = $_POST['quiz_id'];
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // First delete quiz attempts
        $sql = "DELETE FROM student_quiz_attempts WHERE quiz_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete quiz answers
        $sql = "DELETE qa FROM quiz_answers qa 
                JOIN quiz_questions q ON qa.question_id = q.question_id 
                WHERE q.quiz_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete quiz questions
        $sql = "DELETE FROM quiz_questions WHERE quiz_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        
        // Finally delete the quiz
        $sql = "DELETE FROM quizzes WHERE quiz_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "Quiz and all related data deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting quiz: " . $e->getMessage();
    }
}

// Get all quizzes with course and lesson information
$sql = "SELECT q.*, c.title as course_title, l.title as lesson_title,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
        FROM quizzes q 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        ORDER BY q.created_at DESC";

$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Quiz Management</h3>
                    <a href="create-quiz.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Quiz
                    </a>
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
                                    <th style="width: 20%">Title</th>
                                    <th style="width: 20%">Course</th>
                                    <th style="width: 20%">Lesson</th>
                                    <th class="text-center" style="width: 10%">Questions</th>
                                    <th class="text-center" style="width: 10%">Passing Score</th>
                                    <th class="text-center" style="width: 15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while ($quiz = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $count++; ?></td>
                                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['lesson_title']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $quiz['question_count']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?php echo $quiz['passing_score']; ?>%</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="edit-quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit Quiz">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="quiz-questions.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Manage Questions">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <form method="post" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this quiz and all its questions?');">
                                                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                                                        <button type="submit" name="delete_quiz" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Delete Quiz">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-question-circle fa-2x mb-3"></i>
                                                <p class="mb-0">No quizzes found. Click the "Create New Quiz" button to add one.</p>
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