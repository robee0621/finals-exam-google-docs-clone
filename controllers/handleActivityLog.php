<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $documentId = $_GET['document_id'];
    
    // Verify user has access to view the document's activity
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents d 
        WHERE d.id = ? AND (d.author_id = ? OR EXISTS (
            SELECT 1 FROM document_access WHERE document_id = d.id AND user_id = ?
        ) OR ? IN (SELECT id FROM users WHERE role = 'admin'))
    ");
    $stmt->execute([$documentId, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
    
    // Fetch activity logs
    $stmt = $pdo->prepare("
        SELECT al.*, u.username
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        WHERE al.document_id = ?
        ORDER BY al.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$documentId]);
    $activities = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'activities' => $activities]);
}
