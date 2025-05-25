<?php
require_once '../includes/auth.php';
requireLogin();
?>

<div class="messages-panel" id="messagesPanel">
    <div class="messages-header">
        <h3>Document Chat</h3>
        <span class="close-messages">&times;</span>
    </div>
    
    <div class="messages-content">
        <!-- Messages will be loaded here dynamically -->
    </div>
    
    <div class="messages-input">
        <input type="text" id="messageInput" placeholder="Type a message...">
        <button id="sendMessage" class="btn-primary">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>



