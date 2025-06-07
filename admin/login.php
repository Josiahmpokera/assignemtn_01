<?php
require_once '../config/database.php';
require_once '../templates/header.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin'";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($user = mysqli_fetch_assoc($result)) {
                    if (password_verify($password, $user['password'])) {
                        session_start();
                        $_SESSION["admin_id"] = $user['user_id'];
                        $_SESSION["admin_username"] = $user['username'];
                        header("location: dashboard.php");
                        exit;
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "Invalid username or you don't have admin privileges.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Add login-page class to body
echo '<script>document.body.classList.add("login-page");</script>';
?>

    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card mt-5 mb-5">
                <div class="card-header">
                    <h3 class="text-center">Admin Login</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input placeholder="Enter your Username" type="text" name="username" id="username" class="form-control shadow-none" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input placeholder="Enter your Password" type="password" name="password" id="password" class="form-control shadow-none" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

                        <div class="spacer" style="margin-top: 35vh"></div>
<?php require_once '../templates/footer.php'; ?> 