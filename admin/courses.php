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

// Handle course deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'];
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // First delete enrollments
        $sql = "DELETE FROM enrollments WHERE course_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $course_id);
        mysqli_stmt_execute($stmt);
        
        // Then delete modules
        $sql = "DELETE FROM modules WHERE course_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $course_id);
        mysqli_stmt_execute($stmt);
        
        // Finally delete the course
        $sql = "DELETE FROM courses WHERE course_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $course_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "Course and all related data deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting course: " . $e->getMessage();
    }
}

// Get all courses with instructor information
$sql = "SELECT c.*, u.full_name as instructor_name 
        FROM courses c 
        LEFT JOIN users u ON c.instructor_id = u.user_id 
        ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Course Management</h3>
                    <a href="add-course.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Course
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
                                    <th style="width: 30%">Course Title</th>
                                    <th style="width: 20%">Instructor</th>
                                    <th class="text-center" style="width: 15%">Category</th>
                                    <th class="text-center" style="width: 10%">Level</th>
                                    <th class="text-center" style="width: 10%">Price</th>
                                    <th class="text-center" style="width: 10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while ($course = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $count++; ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                    <small class="text-muted"><?php echo substr(htmlspecialchars($course['description']), 0, 50) . '...'; ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($course['category']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($course['level']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success">TZS <?php echo number_format($course['price']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="edit-course.php?id=<?php echo $course['course_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit Course">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="course-modules.php?id=<?php echo $course['course_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Manage Modules">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <form method="post" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this course and all its related data?');">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                        <button type="submit" name="delete_course" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Delete Course">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-book fa-2x mb-3"></i>
                                                <p class="mb-0">No courses found. Click the "Add New Course" button to create one.</p>
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
</style>

<?php require_once '../templates/footer.php'; ?> 