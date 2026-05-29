<?php
session_start();
require 'config.php';

if (isset($_GET['session_id'])) {
    header('Content-Type: application/json');
    $session_id = $_GET['session_id'];
    
    $stmt = $pdo->prepare("SELECT nickname, score FROM player_scores WHERE session_id = ? ORDER BY score DESC LIMIT 10");
    $stmt->execute([$session_id]);
    $scores = $stmt->fetchAll();
    
    echo json_encode($scores);
    exit;
}
?>
