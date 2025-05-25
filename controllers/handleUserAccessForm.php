<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $documentId = $_GET['document_id'];
    
    // Verify user has access to the document
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents 
        WHERE id = ? AND author_id = ?
    ");
    $stmt->execute([$documentId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
    
    if (isset($_GET['search'])) {
        // Search users or get all available users if search is empty
        $search = $_GET['search'] ? '%' . $_GET['search'] . '%' : '%';
        $stmt = $pdo->prepare("
            SELECT u.id, u.username,
                   CASE WHEN da.user_id IS NOT NULL THEN 1 ELSE 0 END as has_access
            FROM users u
            LEFT JOIN document_access da ON u.id = da.user_id AND da.document_id = ?
            WHERE u.username LIKE ? 
            AND u.id != ? 
            AND u.is_suspended = 0
            ORDER BY u.username ASC
        ");
        $stmt->execute([$documentId, $search, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
    } else {
        // Get shared users
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, da.can_edit
            FROM document_access da
            JOIN users u ON da.user_id = u.id
            WHERE da.document_id = ?
        ");
        $stmt->execute([$documentId]);
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verify user is document owner
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents 
        WHERE id = ? AND author_id = ?
    ");
    $stmt->execute([$data['document_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
    
    if ($data['can_edit']) {
        // Grant or update access
        $stmt = $pdo->prepare("
            INSERT INTO document_access (document_id, user_id, can_edit)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE can_edit = ?
        ");
        $stmt->execute([
            $data['document_id'],
            $data['user_id'],
            $data['can_edit'],
            $data['can_edit']
        ]);
    } else {
        // Remove access
        $stmt = $pdo->prepare("
            DELETE FROM document_access
            WHERE document_id = ? AND user_id = ?
        ");
        $stmt->execute([$data['document_id'], $data['user_id']]);
    }
    
    echo json_encode(['success' => true]);
}