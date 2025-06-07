<?php
require_once '../config/database.php';
require_once '../templates/header.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'instructor') {
    header("location: ../login.php");
    exit;
}

// Check if question ID is provided
if (!isset($_GET['id'])) {
    header("location: ../dashboard.php");
    exit;
}

$question_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get question details and verify ownership
$sql = "SELECT q.*, qz.quiz_id, qz.title as quiz_title, qz.description as quiz_description, 
               l.lesson_id, m.course_id, c.instructor_id 
        FROM quiz_questions q 
        JOIN quizzes qz ON q.quiz_id = qz.quiz_id 
        JOIN lessons l ON qz.lesson_id = l.lesson_id 
        JOIN modules m ON l.module_id = m.module_id 
        JOIN courses c ON m.course_id = c.course_id 
        WHERE q.question_id = ? AND c.instructor_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $question_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $question = mysqli_fetch_assoc($result);
        if (!$question) {
            header("location: ../dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get answers for the question
$answers = [];
$sql = "SELECT * FROM quiz_answers WHERE question_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($answer = mysqli_fetch_assoc($result)) {
            $answers[] = $answer;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle question update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_question'])) {
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);
    $points = trim($_POST['points']);
    $answers = $_POST['answers'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];
    $answer_ids = $_POST['answer_ids'] ?? [];
    
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
            // Update question
            $sql = "UPDATE quiz_questions SET question_text = ?, question_type = ?, points = ? WHERE question_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssii", $question_text, $question_type, $points, $question_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update question");
                }
                mysqli_stmt_close($stmt);
            }
            
            // Update or insert answers
            $sql = "INSERT INTO quiz_answers (answer_id, question_id, answer_text, is_correct) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE answer_text = ?, is_correct = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                foreach ($answers as $index => $answer_text) {
                    $answer_id = $answer_ids[$index] ?? null;
                    $is_correct = in_array($index, $correct_answers) ? 1 : 0;
                    mysqli_stmt_bind_param($stmt, "iisisi", 
                        $answer_id, $question_id, $answer_text, $is_correct,
                        $answer_text, $is_correct);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to update answer");
                    }
                }
                mysqli_stmt_close($stmt);
            }
            
            // Delete removed answers
            $sql = "DELETE FROM quiz_answers WHERE question_id = ? AND answer_id NOT IN (" . 
                   implode(',', array_filter($answer_ids)) . ")";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $question_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to delete answers");
                }
                mysqli_stmt_close($stmt);
            }
            
            mysqli_commit($conn);
            $success = "Question updated successfully!";
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
                    <h3>Edit Question</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="questionForm">
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text</label>
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type</label>
                            <select name="question_type" id="question_type" class="form-select" required>
                                <option value="multiple_choice" <?php echo $question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="true_false" <?php echo $question['question_type'] === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                <option value="short_answer" <?php echo $question['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" name="points" id="points" class="form-control" min="1" value="<?php echo $question['points']; ?>" required>
                        </div>
                        
                        <div id="answersContainer">
                            <h4>Answers</h4>
                            <div id="answersList">
                                <?php foreach ($answers as $index => $answer): ?>
                                    <div class="answer-item mb-2">
                                        <div class="input-group">
                                            <input type="hidden" name="answer_ids[]" value="<?php echo $answer['answer_id']; ?>">
                                            <input type="text" name="answers[]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($answer['answer_text']); ?>" required>
                                            <div class="input-group-text">
                                                <input type="checkbox" name="correct_answers[]" 
                                                       value="<?php echo $index; ?>" 
                                                       class="form-check-input" 
                                                       <?php echo $answer['is_correct'] ? 'checked' : ''; ?>>
                                            </div>
                                            <button type="button" class="btn btn-danger remove-answer">Remove</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="addAnswer" class="btn btn-secondary">Add Answer</button>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" name="update_question" class="btn btn-primary">Update Question</button>
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
                    <h5><?php echo htmlspecialchars($question['quiz_title']); ?></h5>
                    <p class="mb-3"><?php echo htmlspecialchars($question['quiz_description']); ?></p>
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
                <input type="hidden" name="answer_ids[]" value="">
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