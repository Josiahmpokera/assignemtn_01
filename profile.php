<?php
require_once 'config/database.php';
require_once 'templates/header.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get user details
$user_sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $user_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate input
    if (empty($full_name) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if email is already taken
        $check_email_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        if ($stmt = mysqli_prepare($conn, $check_email_sql)) {
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already taken.";
            } else {
                // Update profile
                $update_sql = "UPDATE users SET full_name = ?, email = ?";
                $params = [$full_name, $email];
                $types = "ss";
                
                // Update password if provided
                if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";
                    } else {
                        // Verify current password
                        if (password_verify($current_password, $user['password'])) {
                            $update_sql .= ", password = ?";
                            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                            $types .= "s";
                        } else {
                            $error = "Current password is incorrect.";
                        }
                    }
                }
                
                if (empty($error)) {
                    $update_sql .= " WHERE user_id = ?";
                    $params[] = $user_id;
                    $types .= "i";
                    
                    if ($stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                        if (mysqli_stmt_execute($stmt)) {
                            $success = "Profile updated successfully!";
                            // Refresh user data
                            $user['full_name'] = $full_name;
                            $user['email'] = $email;
                        } else {
                            $error = "Something went wrong. Please try again later.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

// Get user's course history
$courses_sql = "SELECT c.*, e.enrollment_date, e.status, e.progress 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.course_id 
                WHERE e.student_id = ? 
                ORDER BY e.enrollment_date DESC";

$enrolled_courses = [];
if ($stmt = mysqli_prepare($conn, $courses_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($course = mysqli_fetch_assoc($result)) {
            $enrolled_courses[] = $course;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="profileForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Course History</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <div class="alert alert-info">
                            You haven't enrolled in any courses yet. 
                            <a href="modules/courses.php">Browse available courses</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrolled_courses as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $course['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $course['progress']; ?>%"
                                                         aria-valuenow="<?php echo $course['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $course['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="modules/course-content.php?id=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-primary">Continue</a>
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
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 