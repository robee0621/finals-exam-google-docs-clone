<?php

require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); // Accept any email input
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate input
    if (strlen($username) < 3) {
        $_SESSION['error'] = 'Username must be at least 3 characters long';
        header('Location: ../views/register.php');
        exit();
    }

    // Validate role
    if (!in_array($role, ['admin', 'user'])) {
        $_SESSION['error'] = 'Invalid role selected';
        header('Location: ../views/register.php');
        exit();
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Username or email already exists';
        header('Location: ../views/register.php');
        exit();
    }

    // Create new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    
    try {
        $stmt->execute([$username, $email, $hashedPassword, $role]);
        
        // Set success message
        $_SESSION['success'] = 'Registration successful! Please login.';
        
        // Redirect to login page instead of auto-login
        header('Location: ../views/login.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: ../views/register.php');
        exit();
    }
}