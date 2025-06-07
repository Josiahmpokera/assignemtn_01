<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("location: users.php");
    exit;
}

// Get user details
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM enrollments WHERE user_id = u.user_id) as enrolled_courses,
        (SELECT COUNT(*) FROM courses WHERE instructor_id = u.user_id) as created_courses,
        (SELECT COUNT(*) FROM student_quiz_attempts WHERE user_id = u.user_id) as quiz_attempts
        FROM users u 
        WHERE u.user_id = ?";

$user = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$user) {
    header("location: users.php");
    exit;
}

// Get enrolled courses
$sql = "SELECT c.*, e.enrolled_at 
        FROM courses c 
        JOIN enrollments e ON c.course_id = e.course_id 
        WHERE e.user_id = ? 
        ORDER BY e.enrolled_at DESC";

$enrolled_courses = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($course = mysqli_fetch_assoc($result)) {
        $enrolled_courses[] = $course;
    }
    mysqli_stmt_close($stmt);
}

// Get created courses (if instructor)
$created_courses = [];
if ($user['role'] == 'instructor') {
    $sql = "SELECT * FROM courses WHERE instructor_id = ? ORDER BY created_at DESC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($course = mysqli_fetch_assoc($result)) {
            $created_courses[] = $course;
        }
        mysqli_stmt_close($stmt);
    }
}

// Get quiz attempts
$sql = "SELECT qa.*, q.title as quiz_title, c.title as course_title, l.title as lesson_title
        FROM student_quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        JOIN lessons l ON q.lesson_id = l.lesson_id
        JOIN courses c ON l.course_id = c.course_id
        WHERE qa.user_id = ?
        ORDER BY qa.attempted_at DESC";

$quiz_attempts = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($attempt = mysqli_fetch_assoc($result)) {
        $quiz_attempts[] = $attempt;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>User Details</h2>
            <a href="users.php" class="btn btn-secondary">Back to Users</a>
        </div>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4>User Information</h4>
                </div>
                <div class="card-body">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Role:</strong> 
                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'instructor' ? 'info' : 'success'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </p>
                    <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4>Statistics</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Enrolled Courses</h5>
                                    <p class="card-text display-4"><?php echo $user['enrolled_courses']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Created Courses</h5>
                                    <p class="card-text display-4"><?php echo $user['created_courses']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Quiz Attempts</h5>
                                    <p class="card-text display-4"><?php echo $user['quiz_attempts']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrolled Courses -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Enrolled Courses</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Category</th>
                                    <th>Level</th>
                                    <th>Enrolled Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolled_courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['category']); ?></td>
                                        <td><?php echo ucfirst($course['level']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($course['enrolled_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Created Courses (for instructors) -->
    <?php if ($user['role'] == 'instructor'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Created Courses</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Category</th>
                                    <th>Level</th>
                                    <th>Price</th>
                                    <th>Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($created_courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['category']); ?></td>
                                        <td><?php echo ucfirst($course['level']); ?></td>
                                        <td>$<?php echo number_format($course['price'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quiz Attempts -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Quiz Attempts</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Course</th>
                                    <th>Lesson</th>
                                    <th>Score</th>
                                    <th>Passed</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['lesson_title']); ?></td>
                                        <td><?php echo $attempt['score']; ?>%</td>
                                        <td>
                                            <span class="badge bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>">
                                                <?php echo $attempt['passed'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($attempt['attempted_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 