<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$sql = "SELECT c.*, u.full_name as instructor_name 
        FROM courses c 
        JOIN users u ON c.instructor_id = u.user_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($category)) {
    $sql .= " AND c.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($level)) {
    $sql .= " AND c.level = ?";
    $params[] = $level;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY c.created_at DESC";

// Get unique categories for filter
$categories_sql = "SELECT DISTINCT category FROM courses WHERE category IS NOT NULL";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row['category'];
}
?>

<div class="container" style="height: 80vh;">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="text-center mb-4">Available Courses</h1>
            
            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search courses..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category == $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="level" class="form-select">
                                <option value="">All Levels</option>
                                <option value="beginner" <?php echo $level == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo $level == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $level == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Courses Grid -->
            <div class="row">
                <?php
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    if (!empty($params)) {
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                    }
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($course = mysqli_fetch_assoc($result)) {
                                ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card course-card h-100">
                                      
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?></p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <span class="badge bg-primary"><?php echo ucfirst($course['level']); ?></span>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                            </p>
                                            <p class="card-text">
                                                <strong>TZS<?php echo number_format($course['price'], 2); ?></strong>
                                            </p>
                                            <a href="course-details.php?id=<?php echo $course['course_id']; ?>" 
                                               class="btn btn-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="col-12"><div class="alert alert-info">No courses found matching your criteria.</div></div>';
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 