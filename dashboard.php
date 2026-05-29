<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->execute([$teacher_id]);
$quizzes = $stmt->fetchAll();

// Handle starting a game session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_quiz_id'])) {
    $quiz_id = $_POST['start_quiz_id'];
    
    // Create a new session
    $stmt = $pdo->prepare("INSERT INTO game_sessions (quiz_id, status) VALUES (?, 'waiting')");
    $stmt->execute([$quiz_id]);
    $session_id = $pdo->lastInsertId();
    
    $_SESSION['game_session_id'] = $session_id;
    header("Location: play_quiz.php?session_id=" . $session_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QuizApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <div>
            <a href="create_quiz.php" class="btn btn-blue">Create New Quiz</a>
            <a href="logout.php" class="btn btn-outline" style="color:var(--kahoot-red); border-color:var(--kahoot-red);">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Your Quizzes</h2>
        <div class="quiz-list">
            <?php if (count($quizzes) > 0): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                        <p>PIN: <strong><?php echo htmlspecialchars($quiz['game_pin']); ?></strong></p>
                        <div class="quiz-actions">
                            <form action="dashboard.php" method="POST" style="display:inline;">
                                <input type="hidden" name="start_quiz_id" value="<?php echo $quiz['id']; ?>">
                                <button type="submit" class="btn btn-green">Host Game</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You haven't created any quizzes yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
