<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $teacher_id = $_SESSION['user_id'];
    
    // Generate a 6-digit random PIN
    $game_pin = rand(100000, 999999);
    
    // Make sure PIN is unique
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE game_pin = ?");
    $stmt->execute([$game_pin]);
    while ($stmt->rowCount() > 0) {
        $game_pin = rand(100000, 999999);
        $stmt->execute([$game_pin]);
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO quizzes (teacher_id, title, description, game_pin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $title, $description, $game_pin]);
        $quiz_id = $pdo->lastInsertId();

        // Process questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q_index => $question) {
                $q_text = trim($question['text']);
                if (empty($q_text)) continue;

                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, time_limit) VALUES (?, ?, ?)");
                $stmt->execute([$quiz_id, $q_text, 20]); // Default 20s
                $question_id = $pdo->lastInsertId();

                // Process answers for this question
                $correct_index = isset($question['correct']) ? (int)$question['correct'] : 0;
                for ($i = 0; $i < 4; $i++) {
                    $ans_text = trim($question['answers'][$i]);
                    $is_correct = ($i === $correct_index) ? 1 : 0;
                    
                    if (!empty($ans_text)) {
                        $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $ans_text, $is_correct]);
                    }
                }
            }
        }
        
        $pdo->commit();
        header("Location: dashboard.php");
        exit;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error = "Error creating quiz: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - QuizApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h2>Create a New Quiz</h2>
        <a href="dashboard.php" class="btn btn-outline" style="color:#333; border-color:#333;">Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
        <form action="create_quiz.php" method="POST" id="create-quiz-form">
            <div class="question-block">
                <h3>Quiz Details</h3>
                <input type="text" name="title" placeholder="Quiz Title" required>
                <input type="text" name="description" placeholder="Quiz Description (Optional)">
            </div>
            
            <div id="questions-container">
                <!-- Question 1 -->
                <div class="question-block">
                    <h3>Question 1</h3>
                    <input type="text" name="questions[0][text]" placeholder="Enter question..." required>
                    
                    <div class="answers-grid">
                        <div class="answer-input">
                            <input type="radio" name="questions[0][correct]" value="0" required title="Mark as correct">
                            <input type="text" name="questions[0][answers][0]" placeholder="Answer 1 (Red)" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[0][correct]" value="1" title="Mark as correct">
                            <input type="text" name="questions[0][answers][1]" placeholder="Answer 2 (Blue)" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[0][correct]" value="2" title="Mark as correct">
                            <input type="text" name="questions[0][answers][2]" placeholder="Answer 3 (Yellow)" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[0][correct]" value="3" title="Mark as correct">
                            <input type="text" name="questions[0][answers][3]" placeholder="Answer 4 (Green)" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 30px;">
                <button type="button" class="btn btn-blue" id="add-question-btn">Add Another Question</button>
                <button type="submit" class="btn btn-green">Save Quiz</button>
            </div>
        </form>
    </div>

    <script>
        let qCount = 1;
        document.getElementById('add-question-btn').addEventListener('click', function() {
            const container = document.getElementById('questions-container');
            const qHTML = `
                <div class="question-block">
                    <h3>Question ${qCount + 1}</h3>
                    <input type="text" name="questions[${qCount}][text]" placeholder="Enter question..." required>
                    
                    <div class="answers-grid">
                        <div class="answer-input">
                            <input type="radio" name="questions[${qCount}][correct]" value="0" required title="Mark as correct">
                            <input type="text" name="questions[${qCount}][answers][0]" placeholder="Answer 1" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[${qCount}][correct]" value="1" title="Mark as correct">
                            <input type="text" name="questions[${qCount}][answers][1]" placeholder="Answer 2" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[${qCount}][correct]" value="2" title="Mark as correct">
                            <input type="text" name="questions[${qCount}][answers][2]" placeholder="Answer 3" required>
                        </div>
                        <div class="answer-input">
                            <input type="radio" name="questions[${qCount}][correct]" value="3" title="Mark as correct">
                            <input type="text" name="questions[${qCount}][answers][3]" placeholder="Answer 4" required>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', qHTML);
            qCount++;
        });
    </script>
</body>
</html>
