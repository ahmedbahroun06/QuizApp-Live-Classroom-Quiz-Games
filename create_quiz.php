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
    
    $game_pin = rand(100000, 999999);
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

        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q_index => $question) {
                $q_text = trim($question['text']);
                if (empty($q_text)) continue;

                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, time_limit) VALUES (?, ?, ?)");
                $stmt->execute([$quiz_id, $q_text, 20]);
                $question_id = $pdo->lastInsertId();

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
        $error = "Error: " . $e->getMessage();
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
    <header class="app-header">
        <h2 style="background: linear-gradient(to right, #fff, var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">QuizApp!</h2>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </header>

    <div class="container" style="max-width: 800px;">
        <h1 style="margin-bottom: 30px;">Create New Challenge</h1>
        
        <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
        
        <form action="create_quiz.php" method="POST" id="create-quiz-form">
            <div class="glass-card card" style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px;">Quiz Metadata</h3>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Midterm History Quiz" required>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="Short summary of the quiz">
                </div>
            </div>
            
            <div id="questions-container">
                <div class="glass-card question-editor-card">
                    <h3>Question 1</h3>
                    <div class="form-group">
                        <input type="text" name="questions[0][text]" class="form-control" placeholder="Enter your question here..." required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="answer-input" style="background: rgba(255,71,87,0.2); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                            <input type="radio" name="questions[0][correct]" value="0" required>
                            <input type="text" name="questions[0][answers][0]" class="form-control" placeholder="Option 1 (Red)" required>
                        </div>
                        <div class="answer-input" style="background: rgba(46,134,222,0.2); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                            <input type="radio" name="questions[0][correct]" value="1">
                            <input type="text" name="questions[0][answers][1]" class="form-control" placeholder="Option 2 (Blue)" required>
                        </div>
                        <div class="answer-input" style="background: rgba(255,165,2,0.2); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                            <input type="radio" name="questions[0][correct]" value="2">
                            <input type="text" name="questions[0][answers][2]" class="form-control" placeholder="Option 3 (Yellow)" required>
                        </div>
                        <div class="answer-input" style="background: rgba(46,213,115,0.2); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                            <input type="radio" name="questions[0][correct]" value="3">
                            <input type="text" name="questions[0][answers][3]" class="form-control" placeholder="Option 4 (Green)" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 40px; margin-bottom: 60px;">
                <button type="button" class="btn btn-outline" id="add-question-btn" style="flex: 1;">+ Add Question</button>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Save & Publish Quiz</button>
            </div>
        </form>
    </div>

    <script>
        let qCount = 1;
        document.getElementById('add-question-btn').addEventListener('click', function() {
            const container = document.getElementById('questions-container');
            const colors = [
                'rgba(255,71,87,0.2)', 'rgba(46,134,222,0.2)', 
                'rgba(255,165,2,0.2)', 'rgba(46,213,115,0.2)'
            ];
            const qHTML = `
                <div class="glass-card question-editor-card" style="animation: fadeIn 0.4s ease-out;">
                    <h3>Question ${qCount + 1}</h3>
                    <div class="form-group">
                        <input type="text" name="questions[${qCount}][text]" class="form-control" placeholder="Enter your question here..." required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        ${[0,1,2,3].map(i => `
                            <div class="answer-input" style="background: ${colors[i]}; padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" name="questions[${qCount}][correct]" value="${i}" required>
                                <input type="text" name="questions[${qCount}][answers][${i}]" class="form-control" placeholder="Option ${i+1}" required>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', qHTML);
            qCount++;
        });
    </script>
</body>
</html>
