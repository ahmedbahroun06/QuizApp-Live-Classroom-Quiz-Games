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
    
    unset($_SESSION['session_id']); // Clear student session when hosting
    $_SESSION['game_session_id'] = $session_id;
    $_SESSION['active_game_role'] = 'teacher';
    header("Location: play_quiz.php?session_id=" . $session_id);
    exit;
}

// Handle deleting a quiz
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_quiz_id'])) {
    $quiz_id = $_POST['delete_quiz_id'];
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$quiz_id, $teacher_id]);
    header("Location: dashboard.php");
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
    <header class="app-header">
        <h2 style="font-size: 1.8rem; background: linear-gradient(to right, #fff, var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">QuizApp!</h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="create_quiz.php" class="btn btn-accent">Create Quiz</a>
            <a href="logout.php" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">Logout</a>
        </div>
    </header>

    <main class="container">
        <h1 style="margin-bottom: 10px;">My Quizzes</h1>
        <p style="color: var(--text-dim); margin-bottom: 40px;">Manage your classroom activities and host live games.</p>
        
        <div class="grid">
            <?php if (count($quizzes) > 0): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="glass-card card">
                        <div style="font-size: 0.8rem; color: var(--accent); font-weight: 700; margin-bottom: 10px;">GAME PIN: <?php echo htmlspecialchars($quiz['game_pin']); ?></div>
                        <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                        <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                        <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 10px;">
                            <form action="dashboard.php" method="POST">
                                <input type="hidden" name="start_quiz_id" value="<?php echo $quiz['id']; ?>">
                                <button type="submit" class="btn btn-success" style="width: 100%;">Host Live Game</button>
                            </form>
                            <form action="dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
                                <input type="hidden" name="delete_quiz_id" value="<?php echo $quiz['id']; ?>">
                                <button type="submit" class="btn btn-outline" style="width: 100%; border-color: var(--danger); color: var(--danger); padding: 8px;">Delete Quiz</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card card text-center" style="grid-column: 1 / -1; padding: 60px;">
                    <p>No quizzes created yet. Start by creating your first challenge!</p>
                    <a href="create_quiz.php" class="btn btn-primary mt-20">Create My First Quiz</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
