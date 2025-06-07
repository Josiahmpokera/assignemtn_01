<?php
require_once 'config/database.php';
require_once 'templates/header.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $sql = "SELECT user_id, username, password, role FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            session_start();
                            
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            
                            header("location: dashboard.php");
                            exit;
                        } else {
                            $error = "Invalid username or password.";
                        }
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="form-container p-4 bg-light">
            <h2 class="text-center mb-4">Login</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label fs-6">Username</label>
                    <input placeholder="Enter your username" type="text" name="username" id="username" class="form-control shadow-none" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label fs-6">Password</label>
                    <input placeholder="Enter your Password" type="password" name="password" id="password" class="form-control shadow-none" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Login</button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Don't have an account?<a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
