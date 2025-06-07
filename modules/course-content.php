<?php
require_once '../config/database.php';
require_once '../templates/header.php';

if (!isset($_SESSION["user_id"])) {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$course_id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Check enrollment
$enrollment_sql = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'";
$is_enrolled = false;

if ($stmt = mysqli_prepare($conn, $enrollment_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $is_enrolled = mysqli_num_rows($result) > 0;
    }
    mysqli_stmt_close($stmt);
}

if (!$is_enrolled) {
    header("location: course-details.php?id=" . $course_id);
    exit;
}

// Get course details
$course_sql = "SELECT c.*, u.full_name as instructor_name 
               FROM courses c 
               JOIN users u ON c.instructor_id = u.user_id 
               WHERE c.course_id = ?";

if ($stmt = mysqli_prepare($conn, $course_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Get modules and lessons with quizzes
$modules_sql = "SELECT m.*, 
                (SELECT COUNT(*) FROM lessons WHERE module_id = m.module_id) as lesson_count
                FROM modules m 
                WHERE m.course_id = ? 
                ORDER BY m.order_number";

$modules = [];
if ($stmt = mysqli_prepare($conn, $modules_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($module = mysqli_fetch_assoc($result)) {
            $modules[] = $module;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get current lesson if specified
$current_lesson = null;
$current_quiz = null;
if (isset($_GET['lesson'])) {
    $lesson_sql = "SELECT l.*, q.quiz_id, q.title as quiz_title, q.description as quiz_description
                   FROM lessons l
                   LEFT JOIN quizzes q ON l.lesson_id = q.lesson_id
                   WHERE l.lesson_id = ?";
    if ($stmt = mysqli_prepare($conn, $lesson_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_GET['lesson']);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $current_lesson = mysqli_fetch_assoc($result);
            
            // Mark lesson as completed
            if ($current_lesson) {
                $completion_sql = "INSERT IGNORE INTO lesson_completion (student_id, lesson_id, module_id, course_id) 
                                 VALUES (?, ?, ?, ?)";
                if ($completion_stmt = mysqli_prepare($conn, $completion_sql)) {
                    mysqli_stmt_bind_param($completion_stmt, "iiii", $user_id, $current_lesson['lesson_id'], 
                                         $current_lesson['module_id'], $course_id);
                    mysqli_stmt_execute($completion_stmt);
                    mysqli_stmt_close($completion_stmt);
                    
                    // Update course progress
                    $progress_sql = "UPDATE enrollments e 
                                   SET progress = (
                                       SELECT ROUND((COUNT(lc.lesson_id) * 100.0 / 
                                       (SELECT COUNT(*) FROM lessons l 
                                        JOIN modules m ON l.module_id = m.module_id 
                                        WHERE m.course_id = ?)), 2)
                                       FROM lesson_completion lc
                                       JOIN lessons l ON lc.lesson_id = l.lesson_id
                                       JOIN modules m ON l.module_id = m.module_id
                                       WHERE lc.student_id = ? AND m.course_id = ?
                                   )
                                   WHERE e.student_id = ? AND e.course_id = ?";
                    if ($progress_stmt = mysqli_prepare($conn, $progress_sql)) {
                        mysqli_stmt_bind_param($progress_stmt, "iiiii", $course_id, $user_id, $course_id, $user_id, $course_id);
                        mysqli_stmt_execute($progress_stmt);
                        mysqli_stmt_close($progress_stmt);
                    }
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get completed lessons for this course
$completed_lessons_sql = "SELECT lesson_id FROM lesson_completion 
                         WHERE student_id = ? AND course_id = ?";
$completed_lessons = [];
if ($stmt = mysqli_prepare($conn, $completed_lessons_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $completed_lessons[] = $row['lesson_id'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Get quiz submissions for this course
$quiz_submissions_sql = "SELECT quiz_id, score FROM quiz_submissions 
                         WHERE student_id = ? AND course_id = ?";
$quiz_submissions = [];
if ($stmt = mysqli_prepare($conn, $quiz_submissions_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $quiz_submissions[$row['quiz_id']] = $row['score'];
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($modules as $module): ?>
                            <div class="list-group-item">
                                <h5 class="mb-1"><?php echo htmlspecialchars($module['title']); ?></h5>
                                <p class="mb-1 small text-muted"><?php echo $module['lesson_count']; ?> lessons</p>
                                
                                <?php
                                $lessons_sql = "SELECT l.*, q.quiz_id, q.title as quiz_title
                                               FROM lessons l
                                               LEFT JOIN quizzes q ON l.lesson_id = q.lesson_id
                                               WHERE l.module_id = ? 
                                               ORDER BY l.order_number";
                                if ($stmt = mysqli_prepare($conn, $lessons_sql)) {
                                    mysqli_stmt_bind_param($stmt, "i", $module['module_id']);
                                    if (mysqli_stmt_execute($stmt)) {
                                        $result = mysqli_stmt_get_result($stmt);
                                        while ($lesson = mysqli_fetch_assoc($result)) {
                                            $is_active = $current_lesson && $current_lesson['lesson_id'] == $lesson['lesson_id'];
                                            $is_completed = in_array($lesson['lesson_id'], $completed_lessons);
                                            ?>
                                            <div class="list-group-item <?php echo $is_active ? 'active' : ''; ?>">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['lesson_id']; ?>" 
                                                       class="text-decoration-none flex-grow-1">
                                                        <?php if ($is_completed): ?>
                                                            <i class="fas fa-check-circle text-success me-2"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                                        <?php if ($lesson['duration']): ?>
                                                            <small class="text-muted float-end">
                                                                <?php echo $lesson['duration']; ?> min
                                                            </small>
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                                
                                                <?php if ($lesson['quiz_id']): ?>
                                                    <div class="mt-2">
                                                        <?php if (isset($quiz_submissions[$lesson['quiz_id']])): ?>
                                                            <a href="quiz-results.php?quiz_id=<?php echo $lesson['quiz_id']; ?>" 
                                                               class="btn btn-sm btn-outline-<?php echo $quiz_submissions[$lesson['quiz_id']] >= 70 ? 'success' : 'danger'; ?> w-100">
                                                                <i class="fas fa-chart-bar me-1"></i>
                                                                View Results (<?php echo number_format($quiz_submissions[$lesson['quiz_id']], 1); ?>%)
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="quiz.php?id=<?php echo $lesson['quiz_id']; ?>" 
                                                               class="btn btn-sm btn-primary w-100">
                                                                <i class="fas fa-question-circle me-1"></i>
                                                                Take Quiz
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if ($current_lesson): ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($current_lesson['title']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($current_lesson['video_url']): ?>
                            <div class="ratio ratio-16x9 mb-4">
                                <iframe src="<?php echo htmlspecialchars($current_lesson['video_url']); ?>" 
                                        allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                        
                        <div class="lesson-content">
                            <?php echo nl2br(htmlspecialchars($current_lesson['content'])); ?>
                        </div>
                        
                        <?php if ($current_lesson['quiz_id']): ?>
                            <div class="mt-4">
                                <?php if (isset($quiz_submissions[$current_lesson['quiz_id']])): ?>
                                    <a href="quiz-results.php?quiz_id=<?php echo $current_lesson['quiz_id']; ?>" 
                                       class="btn btn-<?php echo $quiz_submissions[$current_lesson['quiz_id']] >= 70 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        View Quiz Results (<?php echo number_format($quiz_submissions[$current_lesson['quiz_id']], 1); ?>%)
                                    </a>
                                <?php else: ?>
                                    <a href="quiz.php?id=<?php echo $current_lesson['quiz_id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-question-circle me-1"></i>
                                        Take Quiz
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h4>Welcome to <?php echo htmlspecialchars($course['title']); ?></h4>
                        <p>Select a lesson from the sidebar to begin learning.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.lesson-content {
    line-height: 1.6;
    font-size: 1.1rem;
}

.list-group-item.active {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #212529;
}

.list-group-item.active:hover {
    background-color: #e9ecef;
}

.list-group-item i.fa-check-circle {
    font-size: 1.1em;
}

.list-group-item .btn {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}
</style>

<?php require_once '../templates/footer.php'; ?> 