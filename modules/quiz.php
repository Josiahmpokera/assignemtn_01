<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../templates/header.php';


if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION["user_id"])) {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("location: courses.php");
    exit;
}

$quiz_id = $_GET['id'];
$user_id = $_SESSION["user_id"];


$quiz_sql = "SELECT q.*, l.title as lesson_title, m.course_id, c.title as course_title 
             FROM quizzes q 
             JOIN lessons l ON q.lesson_id = l.lesson_id 
             JOIN modules m ON l.module_id = m.module_id 
             JOIN courses c ON m.course_id = c.course_id 
             WHERE q.quiz_id = ?";

if ($stmt = mysqli_prepare($conn, $quiz_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $quiz = mysqli_fetch_assoc($result);
        
        if (!$quiz) {
            echo '<div class="alert alert-danger">Quiz not found. Please check the quiz ID.</div>';
            require_once '../templates/footer.php';
            exit;
        }
    } else {
        echo '<div class="alert alert-danger">Error executing quiz query: ' . mysqli_error($conn) . '</div>';
        require_once '../templates/footer.php';
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    echo '<div class="alert alert-danger">Error preparing quiz query: ' . mysqli_error($conn) . '</div>';
    require_once '../templates/footer.php';
    exit;
}


$attempt_sql = "SELECT * FROM student_quiz_attempts WHERE student_id = ? AND quiz_id = ?";
$has_attempted = false;
$previous_score = null;

if ($stmt = mysqli_prepare($conn, $attempt_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $quiz_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($attempt = mysqli_fetch_assoc($result)) {
            $has_attempted = true;
            $previous_score = $attempt['score'];
        }
    } else {
        die("Error executing attempt query: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
} else {
    die("Error preparing attempt query: " . mysqli_error($conn));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$has_attempted) {
    $score = 0;
    $total_points = 0;
    
    
    $questions_sql = "SELECT q.*, a.answer_id, a.answer_text, a.is_correct 
                     FROM quiz_questions q 
                     LEFT JOIN quiz_answers a ON q.question_id = a.question_id 
                     WHERE q.quiz_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $questions_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $questions = [];
            $question_number = 1;
            
            while ($row = mysqli_fetch_assoc($result)) {
                if (!isset($questions[$row['question_id']])) {
                    $questions[$row['question_id']] = [
                        'question_id' => $row['question_id'],
                        'question_text' => $row['question_text'],
                        'question_type' => $row['question_type'],
                        'points' => $row['points'],
                        'answers' => []
                    ];
                }
                if ($row['answer_id']) {
                    $questions[$row['question_id']]['answers'][] = [
                        'answer_id' => $row['answer_id'],
                        'answer_text' => $row['answer_text'],
                        'is_correct' => $row['is_correct']
                    ];
                }
            }
            
            if (empty($questions)) {
                echo '<div class="alert alert-warning">No questions have been added to this quiz yet.</div>';
            } else {
             
                foreach ($questions as $question_id => $question) {
                    $total_points += $question['points'];
                    $user_answer = $_POST['question_' . $question_id] ?? null;
                    
                    if ($question['question_type'] == 'multiple_choice') {
                        foreach ($question['answers'] as $answer) {
                            if ($answer['answer_id'] == $user_answer && $answer['is_correct']) {
                                $score += $question['points'];
                            }
                        }
                    } elseif ($question['question_type'] == 'true_false') {
                        if ($user_answer == 'true' && $question['answers'][0]['is_correct'] || 
                            $user_answer == 'false' && !$question['answers'][0]['is_correct']) {
                            $score += $question['points'];
                        }
                    }
                }
                
              
                $attempt_sql = "INSERT INTO student_quiz_attempts (student_id, quiz_id, score) VALUES (?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $attempt_sql)) {
                    mysqli_stmt_bind_param($stmt, "iii", $user_id, $quiz_id, $score);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    $has_attempted = true;
                    $previous_score = $score;
                }
            }
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h2>
                    <p class="mb-0">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
                    <p class="mb-0">Lesson: <?php echo htmlspecialchars($quiz['lesson_title']); ?></p>
                </div>
                
                <div class="card-body">
                    <?php if ($has_attempted): ?>
                        <div class="alert alert-info">
                            <h4>Your Score: <?php echo $previous_score; ?>%</h4>
                            <p>Passing Score: <?php echo $quiz['passing_score']; ?>%</p>
                            <?php if ($previous_score >= $quiz['passing_score']): ?>
                                <div class="alert alert-success">Congratulations! You passed the quiz.</div>
                            <?php else: ?>
                                <div class="alert alert-danger">You need to score higher to pass this quiz.</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form method="post" id="quizForm">
                            <?php
                      
                            $questions_sql = "SELECT q.*, a.answer_id, a.answer_text, a.is_correct 
                                            FROM quiz_questions q 
                                            LEFT JOIN quiz_answers a ON q.question_id = a.question_id 
                                            WHERE q.quiz_id = ? 
                                            ORDER BY q.question_id";
                            if ($stmt = mysqli_prepare($conn, $questions_sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $quiz_id);
                                if (mysqli_stmt_execute($stmt)) {
                                    $result = mysqli_stmt_get_result($stmt);
                                    $questions = [];
                                    $question_number = 1;
                                    
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        if (!isset($questions[$row['question_id']])) {
                                            $questions[$row['question_id']] = [
                                                'question_id' => $row['question_id'],
                                                'question_text' => $row['question_text'],
                                                'question_type' => $row['question_type'],
                                                'points' => $row['points'],
                                                'answers' => []
                                            ];
                                        }
                                        if ($row['answer_id']) {
                                            $questions[$row['question_id']]['answers'][] = [
                                                'answer_id' => $row['answer_id'],
                                                'answer_text' => $row['answer_text'],
                                                'is_correct' => $row['is_correct']
                                            ];
                                        }
                                    }
                                    
                                    if (empty($questions)) {
                                        echo '<div class="alert alert-warning">No questions have been added to this quiz yet.</div>';
                                    } else {
                                        foreach ($questions as $question) {
                                            ?>
                                            <div class="question mb-4">
                                                <h4>Question <?php echo $question_number++; ?></h4>
                                                <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                                                
                                                <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                                    <?php foreach ($question['answers'] as $answer): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="question_<?php echo $question['question_id']; ?>" 
                                                                   value="<?php echo $answer['answer_id']; ?>" required>
                                                            <label class="form-check-label">
                                                                <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php elseif ($question['question_type'] == 'true_false'): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="question_<?php echo $question['question_id']; ?>" 
                                                               value="true" required>
                                                        <label class="form-check-label">True</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="question_<?php echo $question['question_id']; ?>" 
                                                               value="false" required>
                                                        <label class="form-check-label">False</label>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                        }
                                        
                                        echo '<div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Submit Quiz</button>
                                            </div>';
                                    }
                                }
                                mysqli_stmt_close($stmt);
                            }
                            ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 