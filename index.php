<?php
require_once 'config/database.php';
require_once 'templates/header.php';

// Get featured courses
$sql = "SELECT c.*, u.full_name as instructor_name 
        FROM courses c 
        LEFT JOIN users u ON c.instructor_id = u.user_id 
        ORDER BY c.created_at DESC 
        LIMIT 6";

$featured_courses = [];
if ($result = mysqli_query($conn, $sql)) {
    while ($course = mysqli_fetch_assoc($result)) {
        $featured_courses[] = $course;
    }
}
?>

<!-- Hero Section -->
<section class="hero   text-white py-5">
    <div class="container ">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4 text-dark">Learn New Skills Online</h1>
                <p class="lead mb-4 text-dark">Discover thousands of courses taught by industry experts. Start learning today and advance your career.</p>
                <div class="d-flex gap-3">
                    <a href="modules/courses.php" class="btn btn-success ">Browse Courses</a>
                    <a href="register.php" class="btn btn-outline-light ">Get Started</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/images/hero-image.svg" alt="Online Learning" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<hr>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Why Choose Our Platform?</h2>
                <p class="lead text-muted">Experience the best online learning platform</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-laptop-code fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Expert Instructors</h5>
                        <p class="card-text">Learn from industry professionals with years of experience in their fields.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Certification</h5>
                        <p class="card-text">Earn certificates upon course completion to showcase your skills.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Flexible Learning</h5>
                        <p class="card-text">Learn at your own pace with lifetime access to course materials.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Courses Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Featured Courses</h2>
                <p class="lead text-muted">Explore our most popular courses</p>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_courses as $course): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?php echo ucfirst($course['level']); ?></span>
                                <span class="text-muted">$<?php echo number_format($course['price'], 2); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">By <?php echo htmlspecialchars($course['instructor_name']); ?></small>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="modules/course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">View Course</a>
                                <?php else: ?>
                                    <a href="register.php" class="btn btn-primary btn-sm">Enroll Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="modules/courses.php" class="btn btn-primary btn-lg">View All Courses</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">What Our Students Say</h2>
                <p class="lead text-muted">Success stories from our community</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                           
                            <div>
                                <h6 class="mb-0">John Doe</h6>
                                <small class="text-muted">Web Developer</small>
                            </div>
                        </div>
                        <p class="card-text">"The courses are well-structured and the instructors are knowledgeable. I've learned so much and improved my skills significantly."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                           
                            <div>
                                <h6 class="mb-0">Jane Smith</h6>
                                <small class="text-muted">Data Analyst</small>
                            </div>
                        </div>
                        <p class="card-text">"The platform is user-friendly and the support team is always helpful. I've completed several courses and each one was valuable."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                         
                            <div>
                                <h6 class="mb-0">Mike Johnson</h6>
                                <small class="text-muted">UI/UX Designer</small>
                            </div>
                        </div>
                        <p class="card-text">"The quality of the courses exceeded my expectations. The practical projects helped me build a strong portfolio."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 mb-4 rounded-3 bg-success text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Ready to Start Learning?</h2>
        <p class="lead mb-4">Join thousands of students who are already learning with us.</p>
        <a href="register.php" class="btn btn-light btn-lg">Get Started Now</a>
    </div>
</section>

<?php require_once 'templates/footer.php'; ?>
