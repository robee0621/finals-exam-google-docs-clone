
<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$documentId = isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0;

// Verify user has access to view the document's activity
$stmt = $pdo->prepare("
    SELECT d.*, u.username as author_name 
    FROM documents d 
    JOIN users u ON d.author_id = u.id
    WHERE d.id = ? AND (d.author_id = ? OR EXISTS (
        SELECT 1 FROM document_access WHERE document_id = d.id AND user_id = ?
    ) OR ? IN (SELECT id FROM users WHERE role = 'admin'))
");
$stmt->execute([$documentId, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$document = $stmt->fetch();

if (!$document) {
    header('Location: dashboard_user.php');
    exit();
}

// Fetch activity logs
$stmt = $pdo->prepare("
    SELECT al.*, u.username
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    WHERE al.document_id = ?
    ORDER BY al.created_at DESC
");
$stmt->execute([$documentId]);
$activities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - <?php echo htmlspecialchars($document['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .activity-list {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .activity-user {
            font-weight: bold;
            color: var(--primary-color);
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        .activity-content {
            color: var(--text-color);
        }

        .activity-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #f0f0f0;
            border-radius: 4px;
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        .document-info {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .document-info h2 {
            margin: 0;
            color: var(--primary-color);
        }

        .document-meta {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Activity Log</div>
        <div class="nav-links">
            <a href="document_editor.php?id=<?php echo $documentId; ?>" class="btn-back">Back to Document</a>
            <a href="<?php echo isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php'; ?>" class="btn-back">Dashboard</a>
        </div>
    </nav>

    <div class="document-info">
        <h2><?php echo htmlspecialchars($document['title']); ?></h2>
        <div class="document-meta">
            <p>Author: <?php echo htmlspecialchars($document['author_name']); ?></p>
            <p>Created: <?php echo date('M j, Y g:i A', strtotime($document['created_at'])); ?></p>
            <p>Last modified: <?php echo date('M j, Y g:i A', strtotime($document['updated_at'])); ?></p>
        </div>
    </div>

    <div class="activity-list">
        <h3>Activity History</h3>
        <?php if (empty($activities)): ?>
            <p class="text-center">No activity recorded yet.</p>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-header">
                        <span class="activity-user"><?php echo htmlspecialchars($activity['username']); ?></span>
                        <span class="activity-time"><?php echo date('M j, Y g:i:s A', strtotime($activity['created_at'])); ?></span>
                    </div>
                    <div class="activity-content">
                        <span class="activity-type"><?php echo htmlspecialchars($activity['action_type']); ?></span>
                        <?php echo htmlspecialchars($activity['content']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>