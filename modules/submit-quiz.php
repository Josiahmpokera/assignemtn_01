<?php
require_once '../config/database.php';
require_once '../templates/header.php';

if (!isset($_SESSION["user_id"])) {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quiz_id'])) {
    $quiz_id = $_POST['quiz_id'];
    $student_id = $_SESSION["user_id"];
    $total_questions = 0;
    $correct_answers = 0;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get quiz details
        $quiz_sql = "SELECT q.*, c.course_id 
                     FROM quizzes q 
                     JOIN lessons l ON q.lesson_id = l.lesson_id 
                     JOIN modules m ON l.module_id = m.module_id 
                     JOIN courses c ON m.course_id = c.course_id 
                     WHERE q.quiz_id = ?";
        
        if ($stmt = mysqli_prepare($conn, $quiz_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $quiz_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $quiz = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if (!$quiz) {
                throw new Exception("Quiz not found");
            }
            
            // Insert quiz submission
            $submission_sql = "INSERT INTO quiz_submissions (student_id, quiz_id, course_id, total_questions, correct_answers, score) 
                              VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $submission_sql)) {
                mysqli_stmt_bind_param($stmt, "iiiiid", $student_id, $quiz_id, $quiz['course_id'], 
                                     $total_questions, $correct_answers, 0.00);
                mysqli_stmt_execute($stmt);
                $submission_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
            
            // Process each question
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'question_') === 0) {
                    $question_id = substr($key, 9);
                    $total_questions++;
                    
                    // Get correct answer
                    $answer_sql = "SELECT correct_answer FROM quiz_questions WHERE question_id = ?";
                    if ($stmt = mysqli_prepare($conn, $answer_sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $question_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $question = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt);
                        
                        $is_correct = ($value == $question['correct_answer']) ? 1 : 0;
                        if ($is_correct) {
                            $correct_answers++;
                        }
                        
                        // Store student's answer
                        $answer_sql = "INSERT INTO quiz_answers (submission_id, question_id, student_answer, is_correct) 
                                      VALUES (?, ?, ?, ?)";
                        if ($stmt = mysqli_prepare($conn, $answer_sql)) {
                            mysqli_stmt_bind_param($stmt, "iisi", $submission_id, $question_id, $value, $is_correct);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            }
            
            // Calculate score
            $score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;
            
            // Update submission with final score
            $update_sql = "UPDATE quiz_submissions 
                          SET total_questions = ?, correct_answers = ?, score = ? 
                          WHERE submission_id = ?";
            if ($stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($stmt, "iiid", $total_questions, $correct_answers, $score, $submission_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Redirect to results page
            header("location: quiz-results.php?submission_id=" . $submission_id);
            exit;
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error submitting quiz: " . $e->getMessage();
        header("location: quiz.php?id=" . $quiz_id);
        exit;
    }
} else {
    header("location: courses.php");
    exit;
}
?> 