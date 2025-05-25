let quill;
let saving = false;
let saveTimeout;
let lastContent = '';
let lastTitle = '';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                ['image', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    // Store initial content
    lastContent = quill.root.innerHTML;
    lastTitle = document.getElementById('documentTitle').value;

    // Handle document title changes
    const titleInput = document.getElementById('documentTitle');
    titleInput.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        updateSaveStatus('Saving...');
        saveTimeout = setTimeout(saveDocument, 1000);
    });

    // Handle document content changes
    quill.on('text-change', function() {
        clearTimeout(saveTimeout);
        updateSaveStatus('Saving...');
        saveTimeout = setTimeout(saveDocument, 1000);
    });
});

function updateSaveStatus(status, isError = false) {
    const saveStatus = document.getElementById('saveStatus');
    if (saveStatus) {
        saveStatus.textContent = status;
        if (isError) {
            saveStatus.className = 'save-status error';
        } else if (status === 'Saving...') {
            saveStatus.className = 'save-status saving';
        } else {
            saveStatus.className = 'save-status saved';
        }
    }
}

async function saveDocument() {
    if (saving) return;

    saving = true;
    const currentContent = quill.root.innerHTML;
    const currentTitle = document.getElementById('documentTitle').value;

    try {
        const response = await fetch('../controllers/handleDocumentForm.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: documentId || null,
                title: currentTitle,
                content: currentContent
            })
        });

        const data = await response.json();
        
        if (data.success) {
            lastContent = currentContent;
            lastTitle = currentTitle;
            
            if (isNewDocument && data.documentId) {
                // Update documentId and URL for new documents
                documentId = data.documentId;
                isNewDocument = false;
                window.history.replaceState({}, '', `document_editor.php?id=${documentId}`);
            }
            updateSaveStatus('All changes saved');
        } else {
            updateSaveStatus('Error saving', true);
            console.error('Save error:', data.error);
        }
    } catch (error) {
        console.error('Save error:', error);
        updateSaveStatus('Error saving', true);
    } finally {
        saving = false;
    }
}

// Add beforeunload warning
window.addEventListener('beforeunload', function(e) {
    if (saving) {
        e.preventDefault();
        e.returnValue = 'Changes are still being saved. Are you sure you want to leave?';
        return e.returnValue;
    }
});
