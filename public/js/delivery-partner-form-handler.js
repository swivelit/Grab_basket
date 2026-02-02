/**
 * Delivery Partner Form Submission Handler
 * Prevents multiple submissions and provides consistent UI feedback
 */

class DeliveryPartnerFormHandler {
    constructor(formId, submitBtnId) {
        this.form = document.getElementById(formId);
        this.submitBtn = document.getElementById(submitBtnId);
        this.isSubmitting = false;
        this.init();
    }

    init() {
        if (!this.form || !this.submitBtn) {
            console.error('Form or submit button not found');
            return;
        }

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        // Prevent multiple submissions
        if (this.isSubmitting) {
            e.preventDefault();
            this.showToast('Please wait, your application is already being processed...', 'warning');
            return false;
        }

        // Mark as submitting
        this.isSubmitting = true;

        // Add visual feedback
        this.form.classList.add('form-submitting');
        this.addProgressBar();

        // Handle button states
        const submitText = this.submitBtn.querySelector('.submit-text');
        const loadingText = this.submitBtn.querySelector('.loading-text');

        if (submitText && loadingText) {
            this.submitBtn.disabled = true;
            submitText.classList.add('d-none');
            loadingText.classList.remove('d-none');
        }

        // Disable all form inputs
        this.disableFormInputs();

        // Show progress message
        this.showToast('Processing your application, please wait...', 'info');

        // Remove event listener to prevent duplicates
        this.form.removeEventListener('submit', this.handleSubmit);
    }

    disableFormInputs() {
        const inputs = this.form.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            if (input !== this.submitBtn) {
                input.disabled = true;
                input.style.cursor = 'not-allowed';
            }
        });
    }

    addProgressBar() {
        const existingProgressBar = document.querySelector('.submission-progress');
        if (existingProgressBar) {
            existingProgressBar.remove();
        }

        const progressBar = document.createElement('div');
        progressBar.className = 'submission-progress';
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #28a745);
            z-index: 9999;
            transform: scaleX(0);
            transform-origin: left;
            animation: progressBar 3s ease-in-out forwards;
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        `;
        document.body.appendChild(progressBar);

        // Remove progress bar after animation
        setTimeout(() => {
            if (progressBar && progressBar.parentElement) {
                progressBar.remove();
            }
        }, 3500);
    }

    showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.submission-toast');
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed submission-toast`;
        toast.style.cssText = `
            top: 20px; 
            right: 20px; 
            z-index: 10000; 
            min-width: 320px;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        `;

        const iconClass = type === 'success' ? 'check-circle' : 
                         type === 'warning' ? 'exclamation-triangle' : 
                         type === 'error' ? 'exclamation-circle' : 'info-circle';

        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${iconClass} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after delay
        setTimeout(() => {
            if (toast && toast.parentElement) {
                toast.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, type === 'warning' ? 4000 : 6000);
    }

    // Method to reset form if needed
    reset() {
        this.isSubmitting = false;
        this.form.classList.remove('form-submitting');
        
        const inputs = this.form.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            input.disabled = false;
            input.style.cursor = '';
        });

        const submitText = this.submitBtn.querySelector('.submit-text');
        const loadingText = this.submitBtn.querySelector('.loading-text');

        if (submitText && loadingText) {
            this.submitBtn.disabled = false;
            submitText.classList.remove('d-none');
            loadingText.classList.add('d-none');
        }
    }
}

// Global CSS for animations
const globalStyles = document.createElement('style');
globalStyles.textContent = `
    @keyframes progressBar {
        0% { transform: scaleX(0); }
        50% { transform: scaleX(0.8); }
        100% { transform: scaleX(1); }
    }

    @keyframes slideIn {
        0% { 
            transform: translateX(100%); 
            opacity: 0; 
        }
        100% { 
            transform: translateX(0); 
            opacity: 1; 
        }
    }

    @keyframes slideOut {
        0% { 
            transform: translateX(0); 
            opacity: 1; 
        }
        100% { 
            transform: translateX(100%); 
            opacity: 0; 
        }
    }

    .form-submitting {
        pointer-events: none;
        opacity: 0.8;
        position: relative;
    }

    .form-submitting::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(1px);
        z-index: 998;
        border-radius: inherit;
    }

    .loading-text .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;

document.head.appendChild(globalStyles);