<?php
require_once '../config/database.php';
require_once '../templates/header.php';

if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$course_id = $_GET['id'];
$is_enrolled = false;
$enrollment_status = '';

// Get course details
$sql = "SELECT c.*, u.full_name as instructor_name 
        FROM courses c 
        JOIN users u ON c.instructor_id = u.user_id 
        WHERE c.course_id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);
        
        if (!$course) {
            header("location: courses.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Check if user is enrolled
if (isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $check_enrollment_sql = "SELECT status FROM enrollments WHERE student_id = ? AND course_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $check_enrollment_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($enrollment = mysqli_fetch_assoc($result)) {
                $is_enrolled = true;
                $enrollment_status = $enrollment['status'];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle enrollment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll'])) {
    if (!isset($_SESSION["user_id"])) {
        header("location: ../login.php");
        exit;
    }
    
    $enroll_sql = "INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'active')";
    if ($stmt = mysqli_prepare($conn, $enroll_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
        if (mysqli_stmt_execute($stmt)) {
            $is_enrolled = true;
            $enrollment_status = 'active';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                    
                    <div class="mb-4">
                        <span class="badge bg-primary me-2"><?php echo ucfirst($course['level']); ?></span>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                    </div>
                    
                    <h4>Course Description</h4>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    
                    <h4>What You'll Learn</h4>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item">Comprehensive understanding of the subject</li>
                        <li class="list-group-item">Practical skills and knowledge</li>
                        <li class="list-group-item">Hands-on exercises and projects</li>
                        <li class="list-group-item">Quizzes and assessments</li>
                    </ul>
                </div>
            </div>
            
            <!-- Course Modules -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Course Modules</h3>
                </div>
                <div class="card-body">
                    <?php
                    $modules_sql = "SELECT * FROM modules WHERE course_id = ? ORDER BY order_number";
                    if ($stmt = mysqli_prepare($conn, $modules_sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $course_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $result = mysqli_stmt_get_result($stmt);
                            while ($module = mysqli_fetch_assoc($result)) {
                                ?>
                                <div class="module-item mb-3">
                                    <h5><?php echo htmlspecialchars($module['title']); ?></h5>
                                    <p><?php echo htmlspecialchars($module['description']); ?></p>
                                </div>
                                <?php
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Course Details</h3>
                    <p class="card-text">
                        <strong>Price:</strong> TZS <?php echo number_format($course['price'], 2); ?>
                    </p>
                    <p class="card-text">
                        <strong>Level:</strong> <?php echo ucfirst($course['level']); ?>
                    </p>
                    <p class="card-text">
                        <strong>Category:</strong> <?php echo htmlspecialchars($course['category']); ?>
                    </p>
                    
                    <?php if ($is_enrolled): ?>
                        <?php if ($enrollment_status === 'active'): ?>
                            <div class="alert alert-success">
                                You are enrolled in this course
                            </div>
                            <a href="course-content.php?id=<?php echo $course_id; ?>" 
                               class="btn btn-primary w-100">Continue Learning</a>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Your enrollment is <?php echo $enrollment_status; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post">
                            <button type="submit" name="enroll" class="btn btn-primary w-100">
                                Enroll Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 