<?php
/**
 * Modal/Alert System for The Care Bar
 * Include this at the bottom of pages that need notifications
 */
?>

<!-- Modal Container -->
<div id="modalContainer" class="modal-container"></div>

<!-- Modal Styles -->
<style>
    /* Modal Overlay */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }

    /* Modal Content */
    .modal-content {
        background: var(--white);
        border-radius: 12px;
        padding: 30px;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-content {
        transform: scale(1);
    }

    /* Modal Header */
    .modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .modal-header i {
        font-size: 28px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
    }

    /* Modal Body */
    .modal-body {
        margin-bottom: 20px;
        color: var(--gray-500);
        line-height: 1.6;
    }

    /* Modal Footer */
    .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    /* Modal Buttons */
    .modal-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .modal-btn-primary {
        background: var(--pink-400);
        color: var(--white);
    }

    .modal-btn-primary:hover {
        background: var(--pink-300);
    }

    .modal-btn-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
    }

    .modal-btn-secondary:hover {
        background: var(--gray-200);
    }

    /* Success Modal */
    .modal-success .modal-header i {
        color: var(--success);
    }

    /* Error Modal */
    .modal-error .modal-header i {
        color: var(--error);
    }

    /* Warning Modal */
    .modal-warning .modal-header i {
        color: #ffc107;
    }

    /* Info Modal */
    .modal-info .modal-header i {
        color: #0d6efd;
    }
</style>

<!-- Modal Script -->
<script>
    /**
     * Show a modal alert
     * @param {string} type - 'success', 'error', 'warning', 'info'
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {function} callback - Optional callback when confirmed
     * @param {boolean} hasCancel - Show cancel button
     */
    function showModal(type = 'info', title = 'Alert', message = '', callback = null, hasCancel = false) {
        const container = document.getElementById('modalContainer');
        
        // Icon mapping
        const icons = {
            'success': 'ri-checkbox-circle-fill',
            'error': 'ri-error-warning-fill',
            'warning': 'ri-alert-fill',
            'info': 'ri-information-fill'
        };

        // Create modal HTML
        const modalHTML = `
            <div class="modal-overlay ${type === 'info' ? 'modal-' + type : ''} active">
                <div class="modal-content modal-${type}">
                    <div class="modal-header">
                        <i class="${icons[type] || 'ri-information-fill'}"></i>
                        <h3>${title}</h3>
                    </div>
                    <div class="modal-body">${message}</div>
                    <div class="modal-footer">
                        ${hasCancel ? '<button class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>' : ''}
                        <button class="modal-btn modal-btn-primary" onclick="closeModal(${callback ? 'true' : 'false'})">OK</button>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = modalHTML;

        // Handle callback
        window.modalCallback = callback;
    }

    /**
     * Close the modal
     * @param {boolean} confirmed - Whether the action was confirmed
     */
    function closeModal(confirmed = false) {
        const overlay = document.querySelector('.modal-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            setTimeout(() => {
                document.getElementById('modalContainer').innerHTML = '';
                if (confirmed && window.modalCallback && typeof window.modalCallback === 'function') {
                    window.modalCallback();
                }
            }, 300);
        }
    }

    /**
     * Show success message
     */
    function showSuccess(title = 'Success!', message = '', callback = null) {
        showModal('success', title, message, callback);
    }

    /**
     * Show error message
     */
    function showError(title = 'Error!', message = '', callback = null) {
        showModal('error', title, message, callback);
    }

    /**
     * Show warning message
     */
    function showWarning(title = 'Warning!', message = '', callback = null, hasCancel = true) {
        showModal('warning', title, message, callback, hasCancel);
    }

    /**
     * Show info message
     */
    function showInfo(title = 'Info', message = '', callback = null) {
        showModal('info', title, message, callback);
    }

    /**
     * Show confirmation dialog
     */
    function showConfirm(title = 'Confirm', message = '', callback = null) {
        showModal('info', title, message, callback, true);
    }

    // Close modal when clicking overlay
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    });
</script>
