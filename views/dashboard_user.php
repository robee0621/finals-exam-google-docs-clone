<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

// Fetch user's documents
$stmt = $pdo->prepare("
    SELECT d.*, COUNT(da.id) as shared_count 
    FROM documents d 
    LEFT JOIN document_access da ON d.id = da.document_id 
    WHERE d.author_id = ? OR da.user_id = ?
    GROUP BY d.id 
    ORDER BY d.updated_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$documents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Google Docs Clone</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Google Docs Clone</div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../views/login.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>My Documents</h1>
            <button class="btn-primary" onclick="location.href='document_editor.php'">
                <i class="fas fa-plus"></i> New Document
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="documents-grid">
            <?php if (empty($documents)): ?>
                <div class="no-documents">
                    <i class="fas fa-file-alt"></i>
                    <p>No documents yet. Create your first document!</p>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="document-card">
                        <div class="document-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="document-info">
                            <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                            <p>Last modified: <?php echo date('M j, Y g:i A', strtotime($doc['updated_at'])); ?></p>
                            <p>Shared with: <?php echo $doc['shared_count']; ?> users</p>
                        </div>
                        <div class="document-actions">
                            <a href="document_editor.php?id=<?php echo $doc['id']; ?>" class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Share Document</h2>
            <div class="search-container">
                <input type="text" id="userSearch" placeholder="Search users...">
                <div id="searchResults"></div>
            </div>
            <div id="sharedUsers"></div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>