// Get recent quiz results
$quiz_results_sql = "SELECT s.*, q.title as quiz_title, c.title as course_title
                     FROM quiz_submissions s
                     JOIN quizzes q ON s.quiz_id = q.quiz_id
                     JOIN courses c ON s.course_id = c.course_id
                     WHERE s.student_id = ?
                     ORDER BY s.submitted_at DESC
                     LIMIT 5";

$quiz_results = [];
if ($stmt = mysqli_prepare($conn, $quiz_results_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $quiz_results[] = $row;
    }
    mysqli_stmt_close($stmt);
}

<div class="col-md-6">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Quiz Results</h5>
        </div>
        <div class="card-body">
            <?php if (empty($quiz_results)): ?>
                <p class="text-muted">No quiz results available.</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($quiz_results as $result): ?>
                        <a href="quiz-results.php?submission_id=<?php echo $result['submission_id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($result['quiz_title']); ?></h6>
                                <small class="text-<?php echo $result['score'] >= 70 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($result['score'], 1); ?>%
                                </small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($result['course_title']); ?></p>
                            <small class="text-muted">
                                Submitted: <?php echo date('M j, Y', strtotime($result['submitted_at'])); ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 