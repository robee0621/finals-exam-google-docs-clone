<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['title']) || !isset($data['content'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        if (!empty($data['id'])) {
            // Update existing document
            $stmt = $pdo->prepare("
                UPDATE documents 
                SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND (author_id = ? OR EXISTS (
                    SELECT 1 FROM document_access 
                    WHERE document_id = ? AND user_id = ? AND can_edit = 1
                ))
            ");
            $success = $stmt->execute([
                $data['title'],
                $data['content'],
                $data['id'],
                $_SESSION['user_id'],
                $data['id'],
                $_SESSION['user_id']
            ]);
            
            if ($success) {
                // Log the activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (document_id, user_id, action_type, content) 
                    VALUES (?, ?, 'edit', ?)
                ");
                $stmt->execute([$data['id'], $_SESSION['user_id'], 'Document updated']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update document']);
            }
        } else {
            // Create new document
            $stmt = $pdo->prepare("
                INSERT INTO documents (title, content, author_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $data['content'],
                $_SESSION['user_id']
            ]);
            
            $documentId = $pdo->lastInsertId();
            
            // Log the activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (document_id, user_id, action_type, content) 
                VALUES (?, ?, 'create', ?)
            ");
            $stmt->execute([$documentId, $_SESSION['user_id'], 'Document created']);
            
            echo json_encode(['success' => true, 'documentId' => $documentId]);
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }
}