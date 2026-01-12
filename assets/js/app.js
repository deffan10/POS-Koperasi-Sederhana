/**
 * POS Koperasi Al-Farmasi
 * Main JavaScript
 */

// Format currency to Rupiah
function formatRupiah(angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
}

// Parse Rupiah string to number
function parseRupiah(str) {
    if (typeof str === 'number') return str;
    return parseInt(str.replace(/[^0-9]/g, '')) || 0;
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Handle keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F2 - Focus on search/input
    if (e.key === 'F2') {
        e.preventDefault();
        const searchInput = document.getElementById('searchProduct');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // F9 - Save transaction (on POS page)
    if (e.key === 'F9') {
        e.preventDefault();
        const saveBtn = document.getElementById('btnSaveTransaction');
        if (saveBtn && !saveBtn.disabled) {
            saveBtn.click();
        }
    }
    
    // Escape - Clear focus
    if (e.key === 'Escape') {
        document.activeElement.blur();
    }
});
