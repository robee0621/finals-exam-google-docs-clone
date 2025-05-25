let shareModal = document.getElementById('shareModal');
let shareBtn = document.getElementById('shareBtn');
let closeBtn = document.querySelector('.close');
let userSearch = document.getElementById('userSearch');
let searchResults = document.getElementById('searchResults');
let sharedUsers = document.getElementById('sharedUsers');
let searchTimeout;

// Modal controls
shareBtn.addEventListener('click', () => {
    shareModal.style.display = 'block';
    loadSharedUsers();
    // Load all available users when opening modal
    searchUsers('');
});

closeBtn.addEventListener('click', () => {
    shareModal.style.display = 'none';
});

window.addEventListener('click', (e) => {
    if (e.target === shareModal) {
        shareModal.style.display = 'none';
    }
});

// Real-time user search
userSearch.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchUsers, 300);
});

async function searchUsers(searchTerm) {
    try {
        const response = await fetch(`../controllers/handleUserAccessForm.php?search=${encodeURIComponent(searchTerm)}&document_id=${documentId}`);
        const data = await response.json();

        searchResults.innerHTML = '';
        if (data.success && data.users.length > 0) {
            data.users.forEach(user => {
                const userDiv = document.createElement('div');
                userDiv.className = 'user-result';
                userDiv.innerHTML = `
                    <div class="user-info">
                        <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                        <span>${user.username}</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" ${user.has_access ? 'checked' : ''} 
                               onchange="toggleAccess(${user.id}, this.checked)">
                        <span class="slider"></span>
                    </label>
                `;
                searchResults.appendChild(userDiv);
            });
        } else {
            searchResults.innerHTML = '<p class="no-results">No users found</p>';
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.innerHTML = '<p class="error">Error loading users</p>';
    }
}

async function toggleAccess(userId, hasAccess) {
    try {
        const response = await fetch('../controllers/handleUserAccessForm.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                document_id: documentId,
                user_id: userId,
                can_edit: hasAccess
            })
        });

        const data = await response.json();
        if (data.success) {
            loadSharedUsers();
        }
    } catch (error) {
        console.error('Toggle access error:', error);
    }
}

async function loadSharedUsers() {
    try {
        const response = await fetch(`../controllers/handleUserAccessForm.php?document_id=${documentId}`);
        const data = await response.json();

        sharedUsers.innerHTML = '<h3>Shared with:</h3>';
        if (data.users.length === 0) {
            sharedUsers.innerHTML += '<p>No users have access to this document</p>';
            return;
        }

        data.users.forEach(user => {
            const userDiv = document.createElement('div');
            userDiv.className = 'shared-user';
            userDiv.innerHTML = `
                <span>${user.username}</span>
                <label class="switch">
                    <input type="checkbox" ${user.can_edit ? 'checked' : ''} 
                           onchange="toggleAccess(${user.id}, this.checked)">
                    <span class="slider"></span>
                </label>
                <button class="remove-access" onclick="toggleAccess(${user.id}, false)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            sharedUsers.appendChild(userDiv);
        });
    } catch (error) {
        console.error('Load shared users error:', error);
    }
}