<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $documentId = $_GET['document_id'];
    $lastTimestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : null;
    
    // Verify user has access to the document
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents d 
        WHERE d.id = ? AND (d.author_id = ? OR EXISTS (
            SELECT 1 FROM document_access WHERE document_id = d.id AND user_id = ?
        ))
    ");
    $stmt->execute([$documentId, $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
    
    // Fetch messages
    $query = "
        SELECT m.*, u.username 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.document_id = ?
    ";
    $params = [$documentId];
    
    if ($lastTimestamp) {
        $query .= " AND m.created_at > ?";
        $params[] = $lastTimestamp;
    }
    
    $query .= " ORDER BY m.created_at ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['document_id']) || !isset($data['message']) || trim($data['message']) === '') {
        echo json_encode(['success' => false, 'error' => 'Invalid message data']);
        exit();
    }
    
    // Verify user has access to the document
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents d 
        WHERE d.id = ? AND (d.author_id = ? OR EXISTS (
            SELECT 1 FROM document_access WHERE document_id = d.id AND user_id = ?
        ))
    ");
    $stmt->execute([$data['document_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (document_id, user_id, message) 
        VALUES (?, ?, ?)
    ");
    
    try {
        $stmt->execute([$data['document_id'], $_SESSION['user_id'], $data['message']]);
        $messageId = $pdo->lastInsertId();
        
        // Return the newly created message
        $stmt = $pdo->prepare("
            SELECT m.*, u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}