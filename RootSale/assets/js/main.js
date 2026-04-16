// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {
    
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMobileMenuBtn = document.getElementById('closeMobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuSidebar = document.getElementById('mobileMenuSidebar');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.remove('hidden');
            setTimeout(() => {
                mobileMenuSidebar.classList.remove('-translate-x-full');
            }, 10);
        });
    }

    if (closeMobileMenuBtn && mobileMenu) {
        closeMobileMenuBtn.addEventListener('click', () => {
            mobileMenuSidebar.classList.add('-translate-x-full');
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 300);
        });
    }

});

// Global Toast Notification
window.showToast = function(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastContent = document.getElementById('toastContent');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    toastMessage.textContent = message;

    toastContent.className = 'bg-white px-6 py-4 rounded shadow-lg border-l-4 flex items-center gap-3';
    
    if (type === 'success') {
        toastContent.classList.add('border-green-500', 'text-green-800');
        toastIcon.className = 'fa-solid fa-check-circle text-green-500 text-lg';
    } else if (type === 'error') {
        toastContent.classList.add('border-red-500', 'text-red-800');
        toastIcon.className = 'fa-solid fa-exclamation-circle text-red-500 text-lg';
    } else {
        toastContent.classList.add('border-blue-500', 'text-blue-800');
        toastIcon.className = 'fa-solid fa-info-circle text-blue-500 text-lg';
    }

    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.remove('translate-y-[-100%]');
    }, 10);

    setTimeout(() => {
        toast.classList.add('translate-y-[-100%]');
        setTimeout(() => toast.classList.add('hidden'), 300);
    }, 3000);
};
