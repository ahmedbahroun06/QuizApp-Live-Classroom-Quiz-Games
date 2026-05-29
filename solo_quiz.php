<?php
session_start();
require 'config.php';

if (!isset($_GET['quiz_id'])) {
    header("Location: index.php");
    exit;
}

$quiz_id = $_GET['quiz_id'];

// Get quiz info
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz not found.");
}

// Get all questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Get all answers for these questions
$quiz_data = [];
foreach ($questions as $q) {
    $stmt = $pdo->prepare("SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmt->execute([$q['id']]);
    $answers = $stmt->fetchAll();
    
    $quiz_data[] = [
        'id' => $q['id'],
        'question' => $q['question_text'],
        'time_limit' => $q['time_limit'],
        'answers' => $answers
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solo Mode - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-purple">
    <div id="app" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
        <div class="hero-section" id="start-screen">
            <h1 class="hero-logo" style="font-size: 3rem;"><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p style="font-size: 1.5rem; margin-bottom: 30px;">Mode Solo</p>
            <button class="btn btn-primary" style="font-size: 1.5rem; padding: 15px 40px;" onclick="startGame()">Commencer</button>
        </div>
    </div>

    <script>
        const quizData = <?php echo json_encode($quiz_data); ?>;
        let currentQuestionIndex = 0;
        let score = 0;
        let timerInterval = null;

        function startGame() {
            currentQuestionIndex = 0;
            score = 0;
            showQuestion();
        }

        function showQuestion() {
            if (currentQuestionIndex >= quizData.length) {
                showResults();
                return;
            }

            const q = quizData[currentQuestionIndex];
            const app = document.getElementById('app');
            
            let html = `
                <div class="container" style="animation: fadeIn 0.5s ease-out;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div class="glass-card" style="padding: 10px 25px; font-size: 1.2rem; font-weight: bold;">
                            Question ${currentQuestionIndex + 1} / ${quizData.length}
                        </div>
                        <div class="glass-card" style="padding: 10px 25px; font-size: 1.5rem; font-weight: 800; color: var(--warning);">
                            <span id="timer-count">${q.time_limit}</span>s
                        </div>
                    </div>
                    
                    <div class="glass-card question-title" style="margin-top: 0;">
                        ${q.question}
                    </div>
                    
                    <div class="play-grid">
            `;
            
            q.answers.forEach((ans, i) => {
                html += `<button class="ans-card ans-${i}" onclick="checkAnswer(${ans.is_correct}, this)">${ans.answer_text}</button>`;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            app.innerHTML = html;
            startTimer(q.time_limit);
        }

        function startTimer(duration) {
            let timeLeft = duration;
            if (timerInterval) clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                timeLeft--;
                const timerEl = document.getElementById('timer-count');
                if (timerEl) timerEl.innerText = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    handleTimeout();
                }
            }, 1000);
        }

        function checkAnswer(isCorrect, btnElement) {
            if (timerInterval) clearInterval(timerInterval);
            
            // Disable all buttons
            const buttons = document.querySelectorAll('.ans-card');
            buttons.forEach(btn => btn.disabled = true);
            
            if (isCorrect) {
                btnElement.style.border = "5px solid #2ecc71";
                score += 1000;
                setTimeout(() => {
                    currentQuestionIndex++;
                    showQuestion();
                }, 1000);
            } else {
                btnElement.style.border = "5px solid #e74c3c";
                setTimeout(() => {
                    currentQuestionIndex++;
                    showQuestion();
                }, 1000);
            }
        }

        function handleTimeout() {
            const buttons = document.querySelectorAll('.ans-card');
            buttons.forEach(btn => btn.disabled = true);
            
            setTimeout(() => {
                currentQuestionIndex++;
                showQuestion();
            }, 1500);
        }

        function showResults() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="hero-section" style="animation: fadeIn 0.5s ease-out;">
                    <h1 class="hero-logo" style="font-size: 4rem;">Quiz Terminé !</h1>
                    <div class="glass-card" style="padding: 40px; margin: 30px 0;">
                        <h2 style="font-size: 2.5rem; margin-bottom: 10px;">Score Final</h2>
                        <p style="font-size: 3rem; color: var(--accent); font-weight: bold;">${score} PTS</p>
                    </div>
                    <a href="index.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px;">Retour à l'accueil</a>
                </div>
            `;
        }
    </script>
</body>
</html>
