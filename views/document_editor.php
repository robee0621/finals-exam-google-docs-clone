<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$isNewDocument = !isset($_GET['id']);
$document = null;

// If it's a new document, create it first
if ($isNewDocument) {
    $stmt = $pdo->prepare("
        INSERT INTO documents (title, content, author_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute(['Untitled Document', '', $_SESSION['user_id']]);
    $documentId = $pdo->lastInsertId();
    
    // Log the creation
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (document_id, user_id, action_type, content) 
        VALUES (?, ?, 'create', ?)
    ");
    $stmt->execute([$documentId, $_SESSION['user_id'], 'Document created']);
    
    // Redirect to the same page with the new document ID
    header("Location: document_editor.php?id=" . $documentId);
    exit();
}

// Then continue with existing document fetch code
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.username as author_name 
        FROM documents d 
        JOIN users u ON d.author_id = u.id 
        WHERE d.id = ? AND (d.author_id = ? OR EXISTS (
            SELECT 1 FROM document_access WHERE document_id = d.id AND user_id = ?
        ))
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $document = $stmt->fetch();

    if (!$document) {
        header('Location: dashboard_user.php');
        exit();
    }
} else {
    // Initialize empty document if none was found
    $document = [
        'id' => null,
        'title' => 'Untitled Document',
        'content' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $document ? htmlspecialchars($document['title']) : 'New Document'; ?> - Google Docs Clone</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="dashboard_user.php" class="nav-link">Google Docs Clone</a>
        </div>
        <div class="document-controls">
            <input type="text" id="documentTitle" value="<?php echo htmlspecialchars($document['title']); ?>" class="title-input">
            <span id="saveStatus" class="save-status">All changes saved</span>
        </div>
        <div class="document-actions">
            <?php if ($document['id']): ?>
                <button id="shareBtn" class="btn-share">Share</button>
                <button id="messageBtn" class="btn-message">Messages</button>
                <a href="activity_log.php?document_id=<?php echo $document['id']; ?>" class="btn-primary">
                    <i class="fas fa-history"></i> View Activity Log
                </a>
            <?php endif; ?>
            <a href="dashboard_user.php" class="btn-back">Back to Dashboard</a>
        </div>
    </nav>

    <div class="editor-container">
        <div id="editor"><?php echo $document['content']; ?></div>
    </div>

    <!-- Share Modal -->
    <?php if ($document['id']): ?>
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Share Document</h2>
            <div class="search-container">
                <input type="text" id="userSearch" placeholder="Search users...">
                <div id="searchResults"></div>
            </div>
            <div id="sharedUsers" class="shared-users"></div>
        </div>
    </div>

    <!-- Messages Panel -->
    <div id="messagesPanel" class="messages-panel">
        <div class="messages-header">
            <h3>Messages</h3>
            <span class="close-messages">&times;</span>
        </div>
        <div class="messages-content"></div>
        <div class="messages-input">
            <input type="text" id="messageInput" placeholder="Type a message...">
            <button id="sendMessage">Send</button>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        const documentId = <?php echo $document['id'] ? $document['id'] : 'null'; ?>;
        const isNewDocument = <?php echo $document['id'] ? 'false' : 'true'; ?>;
        const currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: '<?php echo htmlspecialchars($_SESSION['username']); ?>'
        };
    </script>
    <script src="../assets/js/autosave.js"></script>
    <?php if ($document['id']): ?>
    <script src="../assets/js/messaging.js"></script>
    <script src="../assets/js/searchUser.js"></script>
    <?php endif; ?>
</body>
</html>