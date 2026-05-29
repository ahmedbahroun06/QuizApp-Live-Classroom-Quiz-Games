<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'teacher' or 'student'

    if (!empty($username) && !empty($password) && in_array($role, ['teacher', 'student'])) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $error = "Username already exists.";
            } else {
                $error = "An error occurred. Please try again.";
            }
        }
    } else {
        $error = "Please fill in all fields correctly.";
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
    <div class="auth-card">
        <h2>Register</h2>
        <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
        <form action="register.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
            <button type="submit" class="btn btn-block btn-blue">Register</button>
        </form>
        <p class="text-center mt-20"><a href="login.php">Already have an account? Login</a></p>
        <p class="text-center mt-20"><a href="index.html">Back to Home</a></p>
    </div>
</body>
</html>
