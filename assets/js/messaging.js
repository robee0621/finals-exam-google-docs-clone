let messagesPanelVisible = false;
let lastMessageTimestamp = null;
const messagesPanel = document.getElementById('messagesPanel');
const messageInput = document.getElementById('messageInput');
const sendMessageBtn = document.getElementById('sendMessage');
const messageBtn = document.getElementById('messageBtn');
const closeMessagesBtn = document.querySelector('.close-messages');
const messagesContent = document.querySelector('.messages-content');

// Toggle messages panel
messageBtn.addEventListener('click', () => {
    messagesPanelVisible = !messagesPanelVisible;
    messagesPanel.style.display = messagesPanelVisible ? 'flex' : 'none';
    if (messagesPanelVisible) {
        loadMessages();
        startMessagePolling();
        messageInput.focus();
    } else {
        stopMessagePolling();
    }
});

closeMessagesBtn.addEventListener('click', () => {
    messagesPanelVisible = false;
    messagesPanel.style.display = 'none';
    stopMessagePolling();
});

// Send message handlers
sendMessageBtn.addEventListener('click', sendMessage);
messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

async function sendMessage() {
    const message = messageInput.value.trim();
    if (!message || !documentId) return;

    try {
        const response = await fetch('../controllers/handleMessageForm.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                document_id: documentId,
                message: message
            })
        });

        const data = await response.json();
        if (data.success) {
            // Clear input and update lastMessageTimestamp
            messageInput.value = '';
            lastMessageTimestamp = data.message.created_at;
            // Append the returned message instead of creating a temporary one
            appendMessage(data.message);
            messageInput.focus();
        } else {
            console.error('Failed to send message:', data.error);
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
}

let messagePollingInterval;

function startMessagePolling() {
    // Clear any existing interval first
    stopMessagePolling();
    // Load messages immediately
    loadMessages();
    // Then start polling
    messagePollingInterval = setInterval(loadMessages, 3000);
}

function stopMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
}

async function loadMessages() {
    if (!documentId) return;

    try {
        const response = await fetch(`../controllers/handleMessageForm.php?document_id=${documentId}&last_timestamp=${lastMessageTimestamp || ''}`);
        const data = await response.json();

        if (data.success && data.messages && data.messages.length > 0) {
            // Clear messages content if this is the first load
            if (!lastMessageTimestamp) {
                messagesContent.innerHTML = '';
            }
            
            data.messages.forEach(message => {
                appendMessage(message);
            });

            lastMessageTimestamp = data.messages[data.messages.length - 1].created_at;
            messagesContent.scrollTop = messagesContent.scrollHeight;
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function appendMessage(message) {
    // Check if message already exists by ID
    if (message.id && document.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
    }

    const messageElement = document.createElement('div');
    messageElement.classList.add('message');
    messageElement.classList.add(message.user_id === currentUser.id ? 'message-sent' : 'message-received');
    if (message.id) {
        messageElement.setAttribute('data-message-id', message.id);
    }
    
    const timestamp = new Date(message.created_at).toLocaleTimeString();
    
    messageElement.innerHTML = `
        <div class="message-header">
            <span class="message-author">${message.username}</span>
            <span class="message-time">${timestamp}</span>
        </div>
        <div class="message-content">${message.message}</div>
    `;
    
    messagesContent.appendChild(messageElement);
    messagesContent.scrollTop = messagesContent.scrollHeight;
}
