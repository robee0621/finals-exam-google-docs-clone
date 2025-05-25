<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verify target user exists and is not an admin
    $stmt = $pdo->prepare("
        SELECT role FROM users 
        WHERE id = ? AND role != 'admin'
    ");
    $stmt->execute([$data['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Invalid user or cannot suspend admin']);
        exit();
    }
    
    // Update user suspension status
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_suspended = ? 
        WHERE id = ?
    ");
    
    try {
        $stmt->execute([$data['is_suspended'], $data['user_id']]);
        
        // Log the action
        $action = $data['is_suspended'] ? 'suspended' : 'unsuspended';
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action_type, content) 
            VALUES (?, 'admin_action', ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], 
            "Admin {$action} user ID: {$data['user_id']}"
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}