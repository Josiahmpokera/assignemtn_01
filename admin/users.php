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

$error = '';
$success = '';

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // First delete enrollments
        $sql = "DELETE FROM enrollments WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete courses if instructor
        $sql = "DELETE FROM courses WHERE instructor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Finally delete the user
        $sql = "DELETE FROM users WHERE user_id = ? AND role != 'admin'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "User and all related data deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Handle role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $sql = "UPDATE users SET role = ? WHERE user_id = ? AND role != 'admin'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "User role updated successfully!";
        } else {
            $error = "Error updating user role: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM enrollments WHERE user_id = u.user_id) as enrolled_courses,
        (SELECT COUNT(*) FROM courses WHERE instructor_id = u.user_id) as created_courses
        FROM users u 
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">User Management</h3>
                    <a href="create-user.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New User
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%">#</th>
                                    <th style="width: 20%">User Details</th>
                                    <th style="width: 20%">Email</th>
                                    <th class="text-center" style="width: 15%">Role</th>
                                    <th class="text-center" style="width: 10%">Enrolled</th>
                                    <th class="text-center" style="width: 10%">Created</th>
                                    <th class="text-center" style="width: 20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $count++; ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="text-center">
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <select name="new_role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                            <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                                        </select>
                                                        <input type="hidden" name="update_role" value="1">
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $user['enrolled_courses']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?php echo $user['created_courses']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="edit-user.php?id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="user-details.php?id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($user['role'] != 'admin'): ?>
                                                        <form method="post" class="d-inline" 
                                                              onsubmit="return confirm('Are you sure you want to delete this user and all their related data?');">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                            <button type="submit" name="delete_user" 
                                                                    class="btn btn-sm btn-outline-danger" 
                                                                    title="Delete User">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-2x mb-3"></i>
                                                <p class="mb-0">No users found. Click the "Add New User" button to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-select-sm {
    width: auto;
    display: inline-block;
}
</style>

<?php require_once '../templates/footer.php'; ?> 