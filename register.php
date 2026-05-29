<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            $_SESSION['success'] = "Account created! You can now login.";
            header("Location: login.php");
            exit;
        } catch (\PDOException $e) {
            $error = "Username already taken.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuizApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="hero-section">
        <div class="glass-card join-form-card">
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 2rem;">Join the Elite</h2>
            <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <select name="role" class="form-control" required style="background: rgba(255,255,255,0.05);">
                        <option value="teacher" style="color: black;">I am a Teacher</option>
                        <option value="student" style="color: black;">I am a Student</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">Create Account</button>
            </form>
            <p style="text-align: center; margin-top: 25px; color: var(--text-dim);">Already a member? <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 700;">Login</a></p>
        </div>
    </div>
</body>
</html>
