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

// Check if instructors table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'instructors'");
if (mysqli_num_rows($table_check) == 0) {
    // Table doesn't exist, create it
    $sql = file_get_contents('create_instructors_table.sql');
    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_next_result($conn));
        $success = "Instructors table created successfully!";
    } else {
        $error = "Error creating instructors table: " . mysqli_error($conn);
    }
}

// Handle instructor deletion
if (isset($_POST['delete_instructor'])) {
    $instructor_id = $_POST['delete_instructor'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, update any courses assigned to this instructor
        $sql = "UPDATE courses SET instructor_id = NULL WHERE instructor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $instructor_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete the instructor
        $sql = "DELETE FROM instructors WHERE instructor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $instructor_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $success = "Instructor deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error deleting instructor: " . $e->getMessage();
    }
}

// Get all instructors with their course count
$sql = "SELECT i.*, COUNT(c.course_id) as course_count 
        FROM instructors i 
        LEFT JOIN courses c ON i.instructor_id = c.instructor_id 
        GROUP BY i.instructor_id 
        ORDER BY i.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Manage Instructors</h3>
                    <a href="add-instructor.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Instructor
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Courses</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($instructor = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                            <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                            <td><?php echo htmlspecialchars($instructor['phone']); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo $instructor['course_count']; ?> courses
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $instructor['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($instructor['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit-instructor.php?id=<?php echo $instructor['instructor_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this instructor?');">
                                                        <button type="submit" name="delete_instructor" 
                                                                value="<?php echo $instructor['instructor_id']; ?>" 
                                                                class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No instructors found. Click "Add New Instructor" to create one.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
}

.btn-group {
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
}
</style>

<?php require_once '../templates/footer.php'; ?> 