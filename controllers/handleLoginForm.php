<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // First check if user exists and get suspension status
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_suspended']) {
            $_SESSION['error'] = 'Your account has been suspended. Please contact an administrator.';
            header('Location: ../views/login.php');
            exit();
        }

        if (password_verify($password, $user['password'])) {
            login($user);
            header('Location: ' . ($user['role'] === 'admin' ? '../views/dashboard_admin.php' : '../views/dashboard_user.php'));
            exit();
        }
    }
    
    $_SESSION['error'] = 'Invalid username or password';
    header('Location: ../views/login.php');
    exit();
}