<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if attempt ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$attempt_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get attempt details and verify ownership
$sql = "SELECT a.*, u.username, u.full_name, q.title as quiz_title, q.description as quiz_description, 
               q.passing_score, l.lesson_id, m.course_id, c.instructor_id 
        FROM student_quiz_attempts a 
        JOIN users u ON a.student_id = u.user_id 
        JOIN quizzes q ON a.quiz_id = q.quiz_id 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE a.attempt_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $attempt_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $attempt = mysqli_fetch_assoc($result);
        if (!$attempt) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get questions and student answers
$questions = [];
$sql = "SELECT q.*, a.answer_text as student_answer 
        FROM quiz_questions q 
        LEFT JOIN quiz_answers a ON q.question_id = a.question_id 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $attempt['quiz_id']);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($question = mysqli_fetch_assoc($result)) {
            // Get correct answers
            $answers_sql = "SELECT * FROM quiz_answers WHERE question_id = ?";
            if ($answer_stmt = mysqli_prepare($conn, $answers_sql)) {
                mysqli_stmt_bind_param($answer_stmt, "i", $question['question_id']);
                if (mysqli_stmt_execute($answer_stmt)) {
                    $answer_result = mysqli_stmt_get_result($answer_stmt);
                    $question['correct_answers'] = [];
                    while ($answer = mysqli_fetch_assoc($answer_result)) {
                        if ($answer['is_correct']) {
                            $question['correct_answers'][] = $answer['answer_text'];
                        }
                    }
                }
                mysqli_stmt_close($answer_stmt);
            }
            $questions[] = $question;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h3>Quiz Attempt Details</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h4>Student Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($attempt['full_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($attempt['username']); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4>Quiz Results</h4>
                        <p><strong>Score:</strong> <?php echo $attempt['score']; ?>%</p>
                        <p><strong>Status:</strong> 
                            <?php if ($attempt['score'] >= $attempt['passing_score']): ?>
                                <span class="text-success">Passed</span>
                            <?php else: ?>
                                <span class="text-danger">Failed</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Completed At:</strong> <?php echo date('F j, Y g:i A', strtotime($attempt['completed_at'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4>Questions and Answers</h4>
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Question <?php echo $index + 1; ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    
                                    <div class="mb-3">
                                        <strong>Student's Answer:</strong>
                                        <p><?php echo htmlspecialchars($question['student_answer'] ?? 'No answer provided'); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Correct Answer(s):</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($question['correct_answers'] as $correct_answer): ?>
                                                <li><?php echo htmlspecialchars($correct_answer); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Points:</strong> <?php echo $question['points']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Quiz Information -->
            <div class="card">
                <div class="card-header">
                    <h3>Quiz Information</h3>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($attempt['quiz_title']); ?></h5>
                    <p class="mb-3"><?php echo htmlspecialchars($attempt['quiz_description']); ?></p>
                    <p><strong>Passing Score:</strong> <?php echo $attempt['passing_score']; ?>%</p>
                    <p><strong>Total Questions:</strong> <?php echo count($questions); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 