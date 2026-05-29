<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'teacher') {
        header("Location: dashboard.php");
        exit;
    }
}

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

            if ($user['role'] == 'teacher') {
                header("Location: dashboard.php");
            } else {
                header("Location: index.html"); // Students just play with PIN from index
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
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
    <div class="auth-card">
        <h2>Login</h2>
        <?php if(isset($_SESSION['success'])) { echo "<p style='color:green;text-align:center;'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); } ?>
        <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn btn-block btn-green">Login</button>
        </form>
        <p class="text-center mt-20"><a href="register.php">Don't have an account? Register</a></p>
        <p class="text-center mt-20"><a href="index.html">Back to Home</a></p>
    </div>
</body>
</html>
