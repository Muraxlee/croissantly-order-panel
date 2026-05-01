document.addEventListener('click', (event) => {
    const sidebarToggle = event.target.closest('[data-sidebar-toggle]');
    if (sidebarToggle) {
        const isHidden = document.body.classList.toggle('sidebar-hidden');
        window.localStorage?.setItem('croissantly-sidebar-hidden', isHidden ? '1' : '0');
        sidebarToggle.setAttribute('aria-label', isHidden ? 'Show side panel' : 'Hide side panel');
        sidebarToggle.setAttribute('title', isHidden ? 'Show side panel' : 'Hide side panel');
        return;
    }

    const fullscreenToggle = event.target.closest('[data-fullscreen-toggle]');
    if (fullscreenToggle) {
        if (document.fullscreenElement) {
            document.exitFullscreen?.();
        } else {
            document.documentElement.requestFullscreen?.();
        }
        return;
    }

    const menuToggle = event.target.closest('[data-menu-toggle]');
    if (menuToggle) {
        const isOpen = document.body.classList.toggle('menu-open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        return;
    }

    if (event.target.closest('[data-menu-close]')) {
        document.body.classList.remove('menu-open');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-label', 'Open menu');
        return;
    }

    const drawerLink = event.target.closest('.sidebar .nav-list a');
    if (drawerLink && window.matchMedia('(max-width: 1000px)').matches) {
        document.body.classList.remove('menu-open');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-label', 'Open menu');
    }

    const openButton = event.target.closest('[data-open-modal]');
    if (openButton) {
        const dialog = document.getElementById(openButton.dataset.openModal);
        const slotDate = openButton.dataset.slotDate;
        const dateInput = dialog?.querySelector('input[name="slot_date"]');
        if (slotDate && dateInput) {
            dateInput.value = slotDate;
        }

        if (dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
        return;
    }

    const closeButton = event.target.closest('[data-close-modal]');
    if (closeButton) {
        closeButton.closest('dialog')?.close();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('menu-open');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-label', 'Open menu');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    if (sidebarToggle && window.localStorage?.getItem('croissantly-sidebar-hidden') === '1') {
        document.body.classList.add('sidebar-hidden');
        sidebarToggle.setAttribute('aria-label', 'Show side panel');
        sidebarToggle.setAttribute('title', 'Show side panel');
    }

    document.querySelectorAll('[data-auto-open-modal]').forEach((dialog) => {
        if (dialog instanceof HTMLDialogElement && typeof dialog.showModal === 'function' && !dialog.open) {
            dialog.showModal();
        }
    });
});

document.addEventListener('fullscreenchange', () => {
    const isFullscreen = Boolean(document.fullscreenElement);
    document.body.classList.toggle('is-fullscreen', isFullscreen);

    const fullscreenToggle = document.querySelector('[data-fullscreen-toggle]');
    if (fullscreenToggle) {
        fullscreenToggle.setAttribute('aria-label', isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
        fullscreenToggle.setAttribute('title', isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const methodInput = form.querySelector('input[name="_method"]');
    const isDelete = methodInput && methodInput.value.toLowerCase() === 'delete';

    if ((isDelete || form.dataset.confirm) && !window.confirm(form.dataset.confirm || 'Are you sure you want to delete this?')) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        event.target.close();
    }
});
