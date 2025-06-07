<?php
require_once '../config/database.php';
require_once '../templates/header.php';

if (!isset($_SESSION["user_id"])) {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['submission_id'])) {
    header("location: courses.php");
    exit;
}

$submission_id = $_GET['submission_id'];
$student_id = $_SESSION["user_id"];

// Get submission details
$submission_sql = "SELECT s.*, q.title as quiz_title, c.title as course_title, 
                   u.full_name as student_name
                   FROM quiz_submissions s
                   JOIN quizzes q ON s.quiz_id = q.quiz_id
                   JOIN courses c ON s.course_id = c.course_id
                   JOIN users u ON s.student_id = u.user_id
                   WHERE s.submission_id = ? AND s.student_id = ?";

if ($stmt = mysqli_prepare($conn, $submission_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $submission_id, $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submission = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$submission) {
        $_SESSION['error'] = "Quiz submission not found";
        header("location: courses.php");
        exit;
    }
    
    // Get detailed answers
    $answers_sql = "SELECT a.*, q.question_text, q.correct_answer
                    FROM quiz_answers a
                    JOIN quiz_questions q ON a.question_id = q.question_id
                    WHERE a.submission_id = ?
                    ORDER BY a.answer_id";
    
    $answers = [];
    if ($stmt = mysqli_prepare($conn, $answers_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $submission_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $answers[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Quiz Results</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Quiz Details</h5>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($submission['course_title']); ?></p>
                            <p><strong>Quiz:</strong> <?php echo htmlspecialchars($submission['quiz_title']); ?></p>
                            <p><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_name']); ?></p>
                            <p><strong>Submitted:</strong> <?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Score Summary</h5>
                            <div class="progress mb-3" style="height: 30px;">
                                <div class="progress-bar <?php echo $submission['score'] >= 70 ? 'bg-success' : 'bg-danger'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $submission['score']; ?>%;" 
                                     aria-valuenow="<?php echo $submission['score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo number_format($submission['score'], 1); ?>%
                                </div>
                            </div>
                            <p><strong>Questions:</strong> <?php echo $submission['total_questions']; ?></p>
                            <p><strong>Correct Answers:</strong> <?php echo $submission['correct_answers']; ?></p>
                            <p><strong>Score:</strong> <?php echo number_format($submission['score'], 1); ?>%</p>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Question Details</h5>
                    <div class="list-group">
                        <?php foreach ($answers as $index => $answer): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Question <?php echo $index + 1; ?></h6>
                                    <?php if ($answer['is_correct']): ?>
                                        <span class="badge bg-success">Correct</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Incorrect</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-2"><?php echo htmlspecialchars($answer['question_text']); ?></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Your Answer:</strong></p>
                                        <p class="text-<?php echo $answer['is_correct'] ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($answer['student_answer']); ?>
                                        </p>
                                    </div>
                                    <?php if (!$answer['is_correct']): ?>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Correct Answer:</strong></p>
                                            <p class="text-success">
                                                <?php echo htmlspecialchars($answer['correct_answer']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="course-content.php?id=<?php echo $submission['course_id']; ?>" class="btn btn-primary">
                            Return to Course
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress {
    background-color: #e9ecef;
    border-radius: 0.25rem;
}

.progress-bar {
    font-size: 1rem;
    font-weight: bold;
    line-height: 30px;
}

.list-group-item {
    border-left: 4px solid transparent;
}

.list-group-item .badge {
    font-size: 0.875rem;
    padding: 0.35em 0.65em;
}
</style>

<?php require_once '../templates/footer.php'; ?> 