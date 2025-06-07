<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if lesson ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$lesson_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Verify that the lesson belongs to the instructor
$sql = "SELECT l.*, m.course_id, c.instructor_id 
        FROM lessons l 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE l.lesson_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $lesson_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $lesson = mysqli_fetch_assoc($result);
        if (!$lesson) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle quiz creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_quiz'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $passing_score = trim($_POST['passing_score']);
    
    if (empty($title) || empty($passing_score)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($passing_score) || $passing_score < 0 || $passing_score > 100) {
        $error = "Passing score must be a number between 0 and 100.";
    } else {
        $sql = "INSERT INTO quizzes (lesson_id, title, description, passing_score) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issi", $lesson_id, $title, $description, $passing_score);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Quiz created successfully!";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get existing quiz
$quiz_sql = "SELECT * FROM quizzes WHERE lesson_id = ?";
$quiz = null;
if ($stmt = mysqli_prepare($conn, $quiz_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $lesson_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $quiz = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Get quiz questions if quiz exists
$questions = [];
if ($quiz) {
    $questions_sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_id";
    if ($stmt = mysqli_prepare($conn, $questions_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $quiz['quiz_id']);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($question = mysqli_fetch_assoc($result)) {
                // Get answers for each question
                $answers_sql = "SELECT * FROM quiz_answers WHERE question_id = ?";
                if ($answer_stmt = mysqli_prepare($conn, $answers_sql)) {
                    mysqli_stmt_bind_param($answer_stmt, "i", $question['question_id']);
                    if (mysqli_stmt_execute($answer_stmt)) {
                        $answer_result = mysqli_stmt_get_result($answer_stmt);
                        $question['answers'] = [];
                        while ($answer = mysqli_fetch_assoc($answer_result)) {
                            $question['answers'][] = $answer;
                        }
                    }
                    mysqli_stmt_close($answer_stmt);
                }
                $questions[] = $question;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!$quiz): ?>
                <!-- Create Quiz Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Create Quiz</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="title" class="form-label">Quiz Title</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="passing_score" class="form-label">Passing Score (%)</label>
                                <input type="number" name="passing_score" id="passing_score" class="form-control" 
                                       min="0" max="100" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="create_quiz" class="btn btn-primary">Create Quiz</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Quiz Management -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h3>
                        <div>
                            <a href="add-question.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                               class="btn btn-success">Add Question</a>
                            <a href="edit-quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                               class="btn btn-primary">Edit Quiz</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-3"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        <p><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</p>
                        
                        <h4 class="mt-4">Questions</h4>
                        <?php if (empty($questions)): ?>
                            <div class="alert alert-info">No questions added yet.</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($questions as $question): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                                                <small>Type: <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></small>
                                                <small class="ms-2">Points: <?php echo $question['points']; ?></small>
                                            </div>
                                            <div class="btn-group">
                                                <a href="edit-question.php?id=<?php echo $question['question_id']; ?>" 
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <form method="post" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                    <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                                    <button type="submit" name="delete_question" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($question['answers'])): ?>
                                            <div class="mt-2">
                                                <strong>Answers:</strong>
                                                <ul class="mb-0">
                                                    <?php foreach ($question['answers'] as $answer): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                            <?php if ($answer['is_correct']): ?>
                                                                <span class="badge bg-success">Correct</span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Lesson Information -->
            <div class="card">
                <div class="card-header">
                    <h3>Lesson Information</h3>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($lesson['title']); ?></h5>
                    <p class="mb-1"><?php echo htmlspecialchars($lesson['content']); ?></p>
                    <small>Duration: <?php echo $lesson['duration']; ?> minutes</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 