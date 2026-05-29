<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QuizApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="hero-section">
        <div class="glass-card join-form-card">
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 2rem;">Welcome Back</h2>
            <?php if(isset($_SESSION['success'])) { echo "<p style='color:var(--success);text-align:center;'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); } ?>
            <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-accent btn-block" style="width: 100%;">Sign In</button>
            </form>
            <p style="text-align: center; margin-top: 25px; color: var(--text-dim);">New here? <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 700;">Create account</a></p>
            <p style="text-align: center; margin-top: 10px;"><a href="index.php" style="color: white; opacity: 0.6; text-decoration: none; font-size: 0.8rem;">← Back to Home</a></p>
        </div>
    </div>
</body>
</html>
