<?php
require_once '../config/database.php';
require_once 'header.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Get statistics
$stats = [
    'total_courses' => 0,
    'total_students' => 0,
    'total_instructors' => 0,
    'total_quizzes' => 0
];

// Get total courses
$sql = "SELECT COUNT(*) as count FROM courses";
if ($result = mysqli_query($conn, $sql)) {
    $stats['total_courses'] = mysqli_fetch_assoc($result)['count'];
}

// Get total students
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
if ($result = mysqli_query($conn, $sql)) {
    $stats['total_students'] = mysqli_fetch_assoc($result)['count'];
}

// Get total instructors
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'instructor'";
if ($result = mysqli_query($conn, $sql)) {
    $stats['total_instructors'] = mysqli_fetch_assoc($result)['count'];
}

// Get total quizzes
$sql = "SELECT COUNT(*) as count FROM quizzes";
if ($result = mysqli_query($conn, $sql)) {
    $stats['total_quizzes'] = mysqli_fetch_assoc($result)['count'];
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Admin Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Courses</h5>
                    <p class="card-text display-4"><?php echo $stats['total_courses']; ?></p>
                    <a href="courses.php" class="text-white">Manage Courses</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text display-4"><?php echo $stats['total_students']; ?></p>
                    <a href="users.php" class="text-white">Manage Students</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Instructors</h5>
                    <p class="card-text display-4"><?php echo $stats['total_instructors']; ?></p>
                    <a href="users.php" class="text-white">Manage Instructors</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Quizzes</h5>
                    <p class="card-text display-4"><?php echo $stats['total_quizzes']; ?></p>
                    <a href="quizzes.php" class="text-white">Manage Quizzes</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="../modules/create-course.php" class="btn btn-primary w-100">Create New Course</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="create-quiz.php" class="btn btn-success w-100">Create New Quiz</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="add-instructor.php" class="btn btn-info w-100">Add New Instructor</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="system-settings.php" class="btn btn-secondary w-100">System Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Recent Activities</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>User</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM activities ORDER BY created_at DESC LIMIT 10";
                                if ($result = mysqli_query($conn, $sql)) {
                                    while ($activity = mysqli_fetch_assoc($result)) {
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 