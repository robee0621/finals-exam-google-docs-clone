<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

// Fetch all documents
$stmt = $pdo->prepare("
    SELECT d.*, u.username as author_name, COUNT(da.id) as shared_count 
    FROM documents d 
    JOIN users u ON d.author_id = u.id
    LEFT JOIN document_access da ON d.id = da.document_id 
    GROUP BY d.id 
    ORDER BY d.updated_at DESC
");
$stmt->execute();
$documents = $stmt->fetchAll();

// Fetch all users except current admin
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(d.id) as document_count,
           MAX(d.updated_at) as last_activity
    FROM users u
    LEFT JOIN documents d ON u.id = d.author_id
    WHERE u.role != 'admin'
    GROUP BY u.id
    ORDER BY u.username
");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Google Docs Clone</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-dashboard">
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-shield-alt me-2"></i>
            Admin Dashboard
        </div>
        <div class="nav-links">
            <span class="welcome-text">
                <i class="fas fa-user-shield"></i>
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="../views/login.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo count($users); ?></div>
                <div class="stat-trend">Active accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-title">Total Documents</div>
                <div class="stat-value"><?php echo count($documents); ?></div>
                <div class="stat-trend">Created documents</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-title">Active Users</div>
                <div class="stat-value"><?php echo count(array_filter($users, fn($u) => !$u['is_suspended'])); ?></div>
                <div class="stat-trend">Currently active</div>
            </div>
        </div>

        <!-- Users Section -->
        <section class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-users me-2"></i> User Management</h2>
            </div>
            <div class="users-grid">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="user-stats">
                            <div class="stat-item">
                                <i class="fas fa-file-alt"></i>
                                <span>Documents: <?php echo $user['document_count']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>Last Active: <?php echo $user['last_activity'] ? date('M j, Y g:i A', strtotime($user['last_activity'])) : 'No activity'; ?></span>
                            </div>
                        </div>
                        <div class="user-actions">
                            <label class="switch">
                                <input type="checkbox" 
                                    <?php echo $user['is_suspended'] ? '' : 'checked'; ?>
                                    onchange="toggleUserStatus(<?php echo $user['id']; ?>, this.checked)"
                                >
                                <span class="slider"></span>
                            </label>
                            <span class="status-label <?php echo $user['is_suspended'] ? 'status-suspended' : 'status-active'; ?>">
                                <?php echo $user['is_suspended'] ? 'Suspended' : 'Active'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Documents Section -->
        <section class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-file-alt me-2"></i> All Documents</h2>
            </div>
            <div class="documents-grid">
                <?php foreach ($documents as $doc): ?>
                    <div class="document-card">
                        <div class="document-header">
                            <div class="document-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                        </div>
                        <div class="document-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($doc['author_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('M j, Y g:i A', strtotime($doc['updated_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-share-alt"></i>
                                <span>Shared with <?php echo $doc['shared_count']; ?> users</span>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>


    <script>
        async function toggleUserStatus(userId, isActive) {
            try {
                const response = await fetch('../controllers/handleSuspendUser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        is_suspended: !isActive
                    })
                });

                const data = await response.json();
                if (data.success) {
                    const statusLabel = event.target.parentElement.nextElementSibling;
                    statusLabel.textContent = isActive ? 'Active' : 'Suspended';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function viewActivityLog(documentId) {
            const modal = document.getElementById('activityModal');
            const activityLog = document.getElementById('activityLog');
            modal.style.display = 'block';

            try {
                const response = await fetch(`../controllers/handleActivityLog.php?document_id=${documentId}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="activity-list">';
                    data.activities.forEach(activity => {
                        html += `
                            <div class="activity-item">
                                <div class="activity-header">
                                    <span class="activity-user">${activity.username}</span>
                                    <span class="activity-time">${new Date(activity.created_at).toLocaleString()}</span>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-type">${activity.action_type}:</span>
                                    ${activity.content}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    activityLog.innerHTML = html;
                }
            } catch (error) {
                console.error('Error:', error);
                activityLog.innerHTML = '<p>Error loading activity log</p>';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('activityModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal when clicking X
        document.querySelector('.close').onclick = function() {
            document.getElementById('activityModal').style.display = 'none';
        }
    </script>
</body>
</html>
