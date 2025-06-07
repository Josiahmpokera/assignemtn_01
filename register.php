<?php
require_once 'config/database.php';
require_once 'templates/header.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = 'student'; // Default role

    // Validate input
    if (empty($username) || empty($password) || empty($email) || empty($full_name)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username exists
        $check_sql = "SELECT user_id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This username is already taken.";
            } else {
                // Insert new user
                $insert_sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $insert_sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $email, $full_name, $role);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Something went wrong. Please try again later.";
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class= "form-container bg-light">
            <h2 class="text-center mb-4">Register</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form class="p-3" id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input placeholder="Enter your username" type="text" name="username" id="username" class="form-control shadow-none" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input placeholder="Enter your email" type="email" name="email" id="email" class="form-control shadow-none" required>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input placeholder="Enter your full name" type="text" name="full_name" id="full_name" class="form-control shadow-none" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input placeholder="Enter your password" type="password" name="password" id="password" class="form-control shadow-none" required>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input placeholder="Confirm your password" type="password" name="confirm_password" id="confirm_password" class="form-control shadow-none" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 