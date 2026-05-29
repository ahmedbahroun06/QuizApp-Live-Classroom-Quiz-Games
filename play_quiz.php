<?php
session_start();
require 'config.php';

// Handle AJAX actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // Determine which session ID to use based on active game role
    if (isset($_SESSION['active_game_role']) && $_SESSION['active_game_role'] == 'teacher') {
        $session_id = isset($_SESSION['game_session_id']) ? $_SESSION['game_session_id'] : 0;
    } else {
        $session_id = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : 0;
    }
    
    if ($session_id == 0) {
        echo json_encode(['error' => 'No session']);
        exit;
    }

    if ($_GET['action'] == 'get_state') {
        $stmt = $pdo->prepare("SELECT gs.status, gs.current_question_id, q.game_pin FROM game_sessions gs JOIN quizzes q ON gs.quiz_id = q.id WHERE gs.id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        
        // Fetch players if waiting
        $players = [];
        if ($session['status'] == 'waiting') {
            $stmt2 = $pdo->prepare("SELECT nickname FROM player_scores WHERE session_id = ?");
            $stmt2->execute([$session_id]);
            $players = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
        
        $question = null;
        $answers = null;
        if ($session['status'] == 'playing' && $session['current_question_id']) {
            $stmt3 = $pdo->prepare("SELECT id, question_text, time_limit FROM questions WHERE id = ?");
            $stmt3->execute([$session['current_question_id']]);
            $question = $stmt3->fetch();
            
            $stmt4 = $pdo->prepare("SELECT id, answer_text FROM answers WHERE question_id = ? ORDER BY id");
            $stmt4->execute([$session['current_question_id']]);
            $answers = $stmt4->fetchAll();
        }

        echo json_encode([
            'status' => $session['status'],
            'current_question_id' => $session['current_question_id'],
            'game_pin' => $session['game_pin'],
            'players' => $players,
            'question' => $question,
            'answers' => $answers
        ]);
        exit;
    }
    
    if ($_GET['action'] == 'teacher_start') {
        // Find first question
        $stmt = $pdo->prepare("SELECT q.id FROM questions q JOIN game_sessions gs ON q.quiz_id = gs.quiz_id WHERE gs.id = ? ORDER BY q.id ASC LIMIT 1");
        $stmt->execute([$session_id]);
        $first_q = $stmt->fetch();
        
        if ($first_q) {
            $stmt = $pdo->prepare("UPDATE game_sessions SET status = 'playing', current_question_id = ? WHERE id = ?");
            $stmt->execute([$first_q['id'], $session_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'No questions found']);
        }
        exit;
    }
    
    if ($_GET['action'] == 'teacher_next') {
        // Find next question
        $stmt = $pdo->prepare("SELECT current_question_id, quiz_id FROM game_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $gs = $stmt->fetch();
        
        $stmt2 = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt2->execute([$gs['quiz_id'], $gs['current_question_id']]);
        $next_q = $stmt2->fetch();
        
        if ($next_q) {
            $stmt = $pdo->prepare("UPDATE game_sessions SET current_question_id = ? WHERE id = ?");
            $stmt->execute([$next_q['id'], $session_id]);
        } else {
            // Finish game
            $stmt = $pdo->prepare("UPDATE game_sessions SET status = 'finished' WHERE id = ?");
            $stmt->execute([$session_id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'submit_answer') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $answer_id = $input['answer_id'];
    $player_id = $_SESSION['player_id'];
    
    // Check if correct
    $stmt = $pdo->prepare("SELECT is_correct FROM answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $ans = $stmt->fetch();
    
    if ($ans && $ans['is_correct']) {
        // Add points (e.g. 1000)
        $stmt = $pdo->prepare("UPDATE player_scores SET score = score + 1000 WHERE id = ?");
        $stmt->execute([$player_id]);
        echo json_encode(['correct' => true]);
    } else {
        echo json_encode(['correct' => false]);
    }
    exit;
}

// Student Joining
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['game_pin'])) {
    $game_pin = trim($_POST['game_pin']);
    $nickname = trim($_POST['nickname']);
    
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE game_pin = ?");
    $stmt->execute([$game_pin]);
    $quiz = $stmt->fetch();
    
    if ($quiz) {
        $stmt = $pdo->prepare("SELECT id FROM game_sessions WHERE quiz_id = ? AND status != 'finished' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$quiz['id']]);
        $session = $stmt->fetch();
        
        if ($session) {
            $stmt = $pdo->prepare("INSERT INTO player_scores (session_id, nickname) VALUES (?, ?)");
            $stmt->execute([$session['id'], $nickname]);
            
            unset($_SESSION['game_session_id']); // Clear teacher session when joining as student
            $_SESSION['player_id'] = $pdo->lastInsertId();
            $_SESSION['session_id'] = $session['id'];
            $_SESSION['nickname'] = $nickname;
            $_SESSION['active_game_role'] = 'student'; // Set active game role
            $role = 'student';
        } else {
            die("Game has not started or is finished.");
        }
    } else {
        die("Invalid Game PIN.");
    }
} else if (isset($_SESSION['game_session_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $_SESSION['active_game_role'] = 'teacher';
    $role = 'teacher';
} else if (isset($_SESSION['session_id'])) {
    $_SESSION['active_game_role'] = 'student';
    $role = 'student';
} else {
    header("Location: index.php");
    exit;
}
$session_val = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : (isset($_SESSION['game_session_id']) ? $_SESSION['game_session_id'] : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play - QuizApp</title>
    <link rel="stylesheet" href="style.css">
    <script>
        const userRole = '<?php echo $role; ?>';
        const sessionId = <?php echo $session_val; ?>;
    </script>
</head>
<body class="bg-purple">
    <div id="app" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
        <h2 class="text-center" style="margin-top: 50px;">Loading Game...</h2>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
