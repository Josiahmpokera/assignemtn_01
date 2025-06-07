<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once 'header.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header("location: users.php");
    exit;
}

$user_id = $_GET['id'];
$error = '';
$success = '';

// Get user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$user = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

if (!$user) {
    header("location: users.php");
    exit;
}

// Handle user update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    $new_password = trim($_POST['new_password']);
    
    // Validate input
    if (empty($username) || empty($full_name) || empty($email) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email already exists for other users
        $sql = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "Username or email already exists.";
            } else {
                // Update user
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ?, password = ? WHERE user_id = ?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ssssssi", $username, $full_name, $email, $role, $status, $hashed_password, $user_id);
                    }
                } else {
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ? WHERE user_id = ?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sssssi", $username, $full_name, $email, $role, $status, $user_id);
                    }
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "User updated successfully!";
                    // Update user data
                    $user['username'] = $username;
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['role'] = $role;
                    $user['status'] = $status;
                } else {
                    $error = "Error updating user: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="userForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <option value="admin" selected>Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" id="new_password" class="form-control">
                            <small class="text-muted">Only fill this if you want to change the password</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="users.php" class="btn btn-secondary">Back to Users</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 