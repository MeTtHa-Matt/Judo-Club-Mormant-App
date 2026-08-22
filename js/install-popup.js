document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('install-app-modal');
    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    const userAgent = navigator.userAgent || '';
    const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    if (!isMobile || isStandalone) {
        return;
    }

    const installModal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false,
    });

    setTimeout(function () {
        installModal.show();
    }, 800);

    const closeButton = document.querySelector('[data-install-close]');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            installModal.hide();
        });
    }
});
