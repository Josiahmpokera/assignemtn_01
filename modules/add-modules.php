<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if course ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$course_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Verify that the course belongs to the instructor
$sql = "SELECT * FROM courses WHERE course_id = ? AND instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $course_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);
        if (!$course) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle module creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $order_number = trim($_POST['order_number']);
    
    if (empty($title) || empty($description) || empty($order_number)) {
        $error = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO modules (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issi", $course_id, $title, $description, $order_number);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Module added successfully!";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get existing modules
$modules_sql = "SELECT * FROM modules WHERE course_id = ? ORDER BY order_number";
$modules = [];
if ($stmt = mysqli_prepare($conn, $modules_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($module = mysqli_fetch_assoc($result)) {
            $modules[] = $module;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Add Modules to: <?php echo htmlspecialchars($course['title']); ?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Module Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Module Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" min="1" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Module</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Modules -->
            <div class="card">
                <div class="card-header">
                    <h3>Existing Modules</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <p>No modules added yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($modules as $module): ?>
                                <div class="list-group-item">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($module['title']); ?></h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($module['description']); ?></p>
                                    <small>Order: <?php echo $module['order_number']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Course Information</h4>
                    <p><strong>Title:</strong> <?php echo htmlspecialchars($course['title']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($course['category']); ?></p>
                    <p><strong>Level:</strong> <?php echo ucfirst($course['level']); ?></p>
                    <p><strong>Price:</strong> TZS <?php echo number_format($course['price'], 2); ?></p>
                    <a href="manage-course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary w-100">Back to Course</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 