<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$quiz_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Verify that the quiz belongs to the instructor
$sql = "SELECT q.*, l.lesson_id, m.course_id, c.instructor_id 
        FROM quizzes q 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE q.quiz_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $quiz = mysqli_fetch_assoc($result);
        if (!$quiz) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get student attempts
$attempts = [];
$sql = "SELECT a.*, u.username, u.full_name 
        FROM student_quiz_attempts a 
        JOIN users u ON a.student_id = u.user_id 
        WHERE a.quiz_id = ? 
        ORDER BY a.completed_at DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($attempt = mysqli_fetch_assoc($result)) {
            $attempts[] = $attempt;
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
                    <h3>Student Attempts for Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($attempts)): ?>
                        <div class="alert alert-info">No student attempts yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Completed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attempts as $attempt): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($attempt['full_name']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($attempt['username']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo $attempt['score']; ?>%
                                                <?php if ($attempt['score'] >= $quiz['passing_score']): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attempt['score'] >= $quiz['passing_score']): ?>
                                                    <span class="text-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="text-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('F j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                                            <td>
                                                <a href="view-attempt.php?id=<?php echo $attempt['attempt_id']; ?>" 
                                                   class="btn btn-sm btn-primary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                    <p class="mb-3"><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <p><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</p>
                    <p><strong>Total Attempts:</strong> <?php echo count($attempts); ?></p>
                    <?php if (!empty($attempts)): ?>
                        <?php
                        $passed = array_filter($attempts, function($a) use ($quiz) {
                            return $a['score'] >= $quiz['passing_score'];
                        });
                        $pass_rate = count($attempts) > 0 ? (count($passed) / count($attempts)) * 100 : 0;
                        ?>
                        <p><strong>Pass Rate:</strong> <?php echo number_format($pass_rate, 1); ?>%</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 