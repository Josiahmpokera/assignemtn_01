<?php
require_once 'config/database.php';
require_once 'templates/header.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

// Get user's enrolled courses and progress
$enrolled_courses_sql = "SELECT c.*, e.progress, e.status 
                        FROM enrollments e 
                        JOIN courses c ON e.course_id = c.course_id 
                        WHERE e.student_id = ? AND e.status = 'active'";
$enrolled_courses = [];

if ($stmt = mysqli_prepare($conn, $enrolled_courses_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $enrolled_courses[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get user's created courses if instructor
$created_courses = [];
if ($role === 'instructor') {
    $created_courses_sql = "SELECT * FROM courses WHERE instructor_id = ?";
    if ($stmt = mysqli_prepare($conn, $created_courses_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $created_courses[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            
            <!-- Enrolled Courses Section -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h3>My Courses</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <div class="alert alert-info">
                            You haven't enrolled in any courses yet. 
                            <a href="modules/courses.php">Browse available courses</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text">
                                                Progress: 
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $course['progress']; ?>%"
                                                         aria-valuenow="<?php echo $course['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $course['progress']; ?>%
                                                    </div>
                                                </div>
                                            </p>
                                            <a href="modules/course-content.php?id=<?php echo $course['course_id']; ?>" 
                                               class="btn btn-primary">Continue Learning</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instructor Section -->
            <?php if ($role === 'instructor'): ?>
                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h3>My Created Courses</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($created_courses)): ?>
                            <div class="alert alert-info">
                                You haven't created any courses yet. 
                                <a href="modules/create-course.php">Create your first course</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($created_courses as $course): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <p class="card-text">
                                                    <span class="badge bg-primary"><?php echo ucfirst($course['level']); ?></span>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                                </p>
                                                <a href="modules/manage-course.php?id=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-primary">Manage Course</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Stats Section -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Enrolled Courses</h5>
                            <p class="card-text display-4"><?php echo count($enrolled_courses); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Completed Courses</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Learning Hours</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
