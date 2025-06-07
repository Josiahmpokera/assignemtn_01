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

// Check if course ID is provided
if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$course_id = $_GET['id'];
$error = '';
$success = '';

// Get course details
$sql = "SELECT * FROM courses WHERE course_id = ?";
$course = null;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $course = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$course) {
    header("location: courses.php");
    exit;
}

// Handle module deletion
if (isset($_POST['delete_module'])) {
    $module_id = $_POST['module_id'];
    $sql = "DELETE FROM modules WHERE module_id = ? AND course_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $module_id, $course_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Module deleted successfully!";
        } else {
            $error = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all modules for this course
$modules = [];
$sql = "SELECT * FROM modules WHERE course_id = ? ORDER BY order_number ASC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Course Modules: <?php echo htmlspecialchars($course['title']); ?></h2>
            <a href="add-module.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">Add New Module</a>
            <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Lessons</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($modules)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No modules found for this course.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($modules as $module): ?>
                                        <tr>
                                            <td><?php echo $module['order_number']; ?></td>
                                            <td><?php echo htmlspecialchars($module['title']); ?></td>
                                            <td><?php echo htmlspecialchars($module['description']); ?></td>
                                            <td>
                                                <?php
                                                $sql = "SELECT COUNT(*) as count FROM lessons WHERE module_id = ?";
                                                if ($stmt = mysqli_prepare($conn, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "i", $module['module_id']);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $count = mysqli_fetch_assoc($result)['count'];
                                                    mysqli_stmt_close($stmt);
                                                    echo $count;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="edit-module.php?id=<?php echo $module['module_id']; ?>" 
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
                                                    <button type="submit" name="delete_module" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this module?');">
                                                        Delete
                                                    </button>
                                                </form>
                                                <a href="module-lessons.php?id=<?php echo $module['module_id']; ?>" 
                                                   class="btn btn-sm btn-info">Lessons</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 