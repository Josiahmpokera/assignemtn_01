<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if quiz ID is provided
if (!isset($_GET['quiz_id'])) {
    header("location: ../dashboard.php");
    exit;
}

$quiz_id = $_GET['quiz_id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Verify that the quiz belongs to the instructor
$sql = "SELECT q.*, l.lesson_id, m.course_id, c.instructor_id 
        FROM quizzes q 
        JOIN lessons l ON q.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE q.quiz_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $quiz = mysqli_fetch_assoc($result);
        if (!$quiz) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle question creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_question'])) {
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);
    $points = trim($_POST['points']);
    $answers = $_POST['answers'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];
    
    if (empty($question_text) || empty($question_type) || empty($points)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($points) || $points < 1) {
        $error = "Points must be a positive number.";
    } elseif (empty($answers)) {
        $error = "Please add at least one answer.";
    } elseif ($question_type === 'multiple_choice' && count($correct_answers) === 0) {
        $error = "Please select at least one correct answer.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert question
            $sql = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, points) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "issi", $quiz_id, $question_text, $question_type, $points);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to create question");
                }
                $question_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
            
            // Insert answers
            $sql = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                foreach ($answers as $index => $answer_text) {
                    $is_correct = in_array($index, $correct_answers) ? 1 : 0;
                    mysqli_stmt_bind_param($stmt, "isi", $question_id, $answer_text, $is_correct);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to create answer");
                    }
                }
                mysqli_stmt_close($stmt);
            }
            
            mysqli_commit($conn);
            $success = "Question added successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Something went wrong. Please try again later.";
        }
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

            <div class="card mb-4">
                <div class="card-header">
                    <h3>Add Question to Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <a href="manage-quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-secondary">Back to Quiz</a>
                </div>
                <div class="card-body">
                    <form method="post" id="questionForm">
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text</label>
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type</label>
                            <select name="question_type" id="question_type" class="form-select" required>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" name="points" id="points" class="form-control" min="1" value="1" required>
                        </div>
                        
                        <div id="answersContainer">
                            <h4>Answers</h4>
                            <div id="answersList">
                                <div class="answer-item mb-2">
                                    <div class="input-group">
                                        <input type="text" name="answers[]" class="form-control" placeholder="Answer text" required>
                                        <div class="input-group-text">
                                            <input type="checkbox" name="correct_answers[]" value="0" class="form-check-input">
                                        </div>
                                        <button type="button" class="btn btn-danger remove-answer">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="addAnswer" class="btn btn-secondary">Add Answer</button>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" name="create_question" class="btn btn-primary">Add Question</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Quiz Information -->
            <div class="card">
                <div class="card-header">
                    <h3>Quiz Information</h3>
                </div>
                <div class="card-body">
                    <p class="mb-3"><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <p><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const answersList = document.getElementById('answersList');
    const addAnswerBtn = document.getElementById('addAnswer');
    const questionType = document.getElementById('question_type');
    
    // Add answer
    addAnswerBtn.addEventListener('click', function() {
        const answerCount = answersList.children.length;
        const answerItem = document.createElement('div');
        answerItem.className = 'answer-item mb-2';
        answerItem.innerHTML = `
            <div class="input-group">
                <input type="text" name="answers[]" class="form-control" placeholder="Answer text" required>
                <div class="input-group-text">
                    <input type="checkbox" name="correct_answers[]" value="${answerCount}" class="form-check-input">
                </div>
                <button type="button" class="btn btn-danger remove-answer">Remove</button>
            </div>
        `;
        answersList.appendChild(answerItem);
    });
    
    // Remove answer
    answersList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-answer')) {
            e.target.closest('.answer-item').remove();
        }
    });
    
    // Handle question type change
    questionType.addEventListener('change', function() {
        const isMultipleChoice = this.value === 'multiple_choice';
        const checkboxes = document.querySelectorAll('input[name="correct_answers[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.type = isMultipleChoice ? 'checkbox' : 'radio';
        });
    });
});
</script>

<?php require_once '../templates/footer.php'; ?> 