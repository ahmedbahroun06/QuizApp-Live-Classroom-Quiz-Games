<?php
// config.php
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? '127.0.0.1';
    $db = ltrim($parts['path'] ?? '/quiz_app', '/');
    $user = $parts['user'] ?? 'root';
    $pass = $parts['pass'] ?? '';
} else {
    $host = getenv('DB_HOST') ?: getenv('DATABASE_HOST') ?: '127.0.0.1';
    $db   = getenv('DB_NAME') ?: getenv('DATABASE_NAME') ?: 'quiz_app';
    $user = getenv('DB_USER') ?: getenv('DATABASE_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: getenv('DATABASE_PASSWORD') ?: ''; // Default XAMPP password is empty
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed. Please ensure the database 'quiz_app' is created and running.");
}
?>
