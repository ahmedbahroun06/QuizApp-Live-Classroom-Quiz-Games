<?php
// index.php - Page d'accueil moderne pour les joueurs
session_start();
require 'config.php';

// Récupérer tous les quiz pour le mode "Explorer"
$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY created_at DESC");
$all_quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizApp - Accueil Joueur</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles spécifiques pour la navigation joueur */
        .player-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-btn {
            padding: 12px 30px;
            border-radius: 50px; /* Coins très arrondis */
            font-size: 1.1rem;
            text-decoration: none;
            color: white;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-explorer { background: linear-gradient(45deg, #2ecc71, #27ae60); } /* Vert */
        .btn-jouer { background: linear-gradient(45deg, #f39c12, #e67e22); }    /* Orange */
        .btn-login { background: linear-gradient(45deg, #3498db, #2980b9); }    /* Bleu */

        .nav-btn:hover {
            transform: scale(1.1); /* Agrandissement léger */
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            filter: brightness(1.1);
        }

        .section-container {
            display: none; /* Caché par défaut */
            animation: fadeIn 0.5s ease-out;
        }

        .active-section {
            display: block;
        }

        .explorer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 40px 5%;
        }

        .quiz-card-player {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            text-align: center;
            display: flex;
            flex-direction: column;
            border: 3px solid transparent;
        }

        .quiz-card-player:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.5);
        }

        .card-header {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        /* Couleurs dynamiques pour les en-têtes de cartes */
        .quiz-card-player:nth-child(4n+1) .card-header { background: linear-gradient(135deg, #FF6B6B, #FF8E8B); }
        .quiz-card-player:nth-child(4n+2) .card-header { background: linear-gradient(135deg, #4ECDC4, #55E6DB); }
        .quiz-card-player:nth-child(4n+3) .card-header { background: linear-gradient(135deg, #FFD166, #FFDF8A); }
        .quiz-card-player:nth-child(4n+4) .card-header { background: linear-gradient(135deg, #9B59B6, #B371CD); }

        .card-body {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .card-desc {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .btn-play-card {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.1rem;
            display: inline-block;
            transition: background 0.3s;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.4);
        }

        .quiz-card-player:hover .btn-play-card {
            background: #2980b9;
        }
    </style>
</head>
<body>

    <!-- Barre de Navigation Joueur -->
    <nav class="player-nav">
        <button class="nav-btn btn-explorer" onclick="showSection('explorer')">Explorer</button>
        <button class="nav-btn btn-jouer" onclick="showSection('jouer')">Jouer (PIN)</button>
        <a href="login.php" class="nav-btn btn-login">Espace Prof</a>
    </nav>

    <!-- Section EXPLORER (Solo Mode) -->
    <div id="explorer" class="section-container active-section">
        <div class="container">
            <h1 class="text-center" style="margin-top: 30px; font-size: 3rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">Explorer les Quiz</h1>
            <p class="text-center" style="color: rgba(255,255,255,0.9); font-size: 1.2rem; font-weight: 600;">Choisissez un quiz et testez vos connaissances en mode solo !</p>
            
            <div class="explorer-grid">
                <?php if (count($all_quizzes) > 0): ?>
                    <?php foreach ($all_quizzes as $quiz): ?>
                        <div class="quiz-card-player" onclick="window.location.href='solo_quiz.php?quiz_id=<?php echo $quiz['id']; ?>'">
                            <div class="card-header">
                                🎮
                            </div>
                            <div class="card-body">
                                <div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                    <p class="card-desc"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                </div>
                                <div>
                                    <span class="btn-play-card">Jouer en Solo</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center" style="grid-column: 1/-1;">Aucun quiz disponible pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Section JOUER (Mode PIN) -->
    <div id="jouer" class="section-container">
        <div class="hero-section" style="height: auto; padding: 60px 20px;">
            <div class="hero-logo" style="font-size: 3rem;">Prêt à jouer ?</div>
            <div class="glass-card join-form-card">
                <form action="play_quiz.php" method="POST">
                    <input type="text" name="game_pin" id="pin-input" placeholder="CODE PIN" required autocomplete="off">
                    <input type="text" name="nickname" placeholder="TON PSEUDO" required autocomplete="off">
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.2rem;">C'est parti !</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour changer de section sans recharger la page
        function showSection(id) {
            // Cacher toutes les sections
            document.querySelectorAll('.section-container').forEach(sec => {
                sec.classList.remove('active-section');
            });
            // Afficher la section demandée
            document.getElementById(id).classList.add('active-section');
        }
    </script>
</body>
</html>
