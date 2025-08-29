// Svečani Prijemi - JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide flash messages after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Form validation improvements
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Table capacity warning
    const tableSelects = document.querySelectorAll('select[name="table_id"]');
    tableSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.text.includes('0 slobodnih')) {
                const warning = document.createElement('small');
                warning.className = 'text-warning d-block mt-1';
                warning.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Stol je pun!';
                
                // Remove existing warnings
                const existingWarnings = this.parentNode.querySelectorAll('.text-warning');
                existingWarnings.forEach(w => w.remove());
                
                if (selectedOption.value) {
                    this.parentNode.appendChild(warning);
                }
            } else {
                // Remove warnings if table is not full
                const existingWarnings = this.parentNode.querySelectorAll('.text-warning');
                existingWarnings.forEach(w => w.remove());
            }
        });
    });

    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Loading state for buttons
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Molim sačekajte...';
                this.disabled = true;
                
                // Re-enable after 5 seconds as failsafe
                setTimeout(() => {
                    this.disabled = false;
                    // Restore original text (you might want to store this in data attribute)
                }, 5000);
            }
        });
    });

    // Guest search functionality (if needed in future)
    const searchInputs = document.querySelectorAll('.guest-search');
    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const guestItems = document.querySelectorAll('.guest-item, .guest-item-small');
            
            guestItems.forEach(function(item) {
                const guestName = item.querySelector('.guest-name, strong');
                if (guestName) {
                    const text = guestName.textContent.toLowerCase();
                    const parent = item.closest('.guest-item, .unassigned-guest');
                    if (text.includes(searchTerm)) {
                        parent.style.display = '';
                    } else {
                        parent.style.display = 'none';
                    }
                }
            });
        });
    });

    // Confirm deletion actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm-delete') || 'Da li ste sigurni da želite da obrišete?';
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

    // Auto-focus first form input
    const firstInput = document.querySelector('form input:not([type="hidden"]):first-of-type');
    if (firstInput) {
        firstInput.focus();
    }

    // Real-time capacity counter for tables
    const capacityInputs = document.querySelectorAll('input[name="capacity"]');
    capacityInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            let feedback = this.parentNode.querySelector('.capacity-feedback');
            
            if (!feedback) {
                feedback = document.createElement('small');
                feedback.className = 'capacity-feedback text-muted d-block mt-1';
                this.parentNode.appendChild(feedback);
            }
            
            if (value > 0) {
                feedback.innerHTML = `<i class="fas fa-chair me-1"></i>${value} ${value === 1 ? 'mesto' : value < 5 ? 'mesta' : 'mesta'}`;
            } else {
                feedback.innerHTML = '';
            }
        });
    });
});

// Utility functions
function showToast(message, type = 'info') {
    // Create toast notification (Bootstrap 5 style)
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Export functions for use in other scripts
window.svecaniPrijemi = {
    showToast: showToast
};