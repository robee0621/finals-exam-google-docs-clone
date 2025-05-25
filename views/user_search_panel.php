<?php

require_once '../includes/auth.php';
requireLogin();
?>

<div id="userSearchPanel" class="user-search-panel">
    <div class="search-header">
        <h3>Share with Users</h3>
        <span class="close-search">&times;</span>
    </div>

    <div class="search-container">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" 
                   id="userSearchInput" 
                   placeholder="Search users..." 
                   autocomplete="off">
        </div>
        <div id="searchResults" class="search-results"></div>
    </div>

    <div class="shared-users-container">
        <h4>Shared with</h4>
        <div id="sharedUsersList" class="shared-users-list"></div>
    </div>
</div>

<style>

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearchInput');
    const searchResults = document.getElementById('searchResults');
    const sharedUsersList = document.getElementById('sharedUsersList');
    let searchTimeout;

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length >= 2) {
                searchUsers(searchTerm);
            } else {
                searchResults.innerHTML = '';
            }
        }, 300);
    });

    async function searchUsers(searchTerm) {
        try {
            const response = await fetch(`../controllers/handleUserAccessForm.php?search=${encodeURIComponent(searchTerm)}&document_id=${documentId}`);
            const data = await response.json();

            if (data.success) {
                displaySearchResults(data.users);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    function displaySearchResults(users) {
        searchResults.innerHTML = users.map(user => `
            <div class="search-result-item">
                <div class="user-info">
                    <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                    <span>${user.username}</span>
                </div>
                <label class="switch">
                    <input type="checkbox" ${user.has_access ? 'checked' : ''} 
                           onchange="toggleAccess(${user.id}, this.checked)">
                    <span class="slider"></span>
                </label>
            </div>
        `).join('');
    }

    async function loadSharedUsers() {
        try {
            const response = await fetch(`../controllers/handleUserAccessForm.php?document_id=${documentId}`);
            const data = await response.json();

            if (data.success) {
                sharedUsersList.innerHTML = data.users.map(user => `
                    <div class="shared-user-item">
                        <div class="user-info">
                            <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                            <span>${user.username}</span>
                        </div>
                        <div class="permission-toggle">
                            <label class="switch">
                                <input type="checkbox" ${user.can_edit ? 'checked' : ''} 
                                       onchange="toggleAccess(${user.id}, this.checked)">
                                <span class="slider"></span>
                            </label>
                            <button class="remove-user" onclick="toggleAccess(${user.id}, false)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Load shared users error:', error);
        }
    }

    // Initial load of shared users
    loadSharedUsers();
});
</script>