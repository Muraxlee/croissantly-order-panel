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
        const wantsFullscreen = window.localStorage?.getItem('croissantly-fullscreen-mode') !== '1';

        window.localStorage?.setItem('croissantly-fullscreen-mode', wantsFullscreen ? '1' : '0');
        document.body.classList.toggle('is-fullscreen', wantsFullscreen);

        if (!wantsFullscreen && document.fullscreenElement) {
            document.exitFullscreen?.();
        } else if (wantsFullscreen && !document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        }

        updateFullscreenToggle();
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

    const pageLink = event.target.closest('a[href]');
    if (shouldLoadInPlace(pageLink)) {
        event.preventDefault();
        loadPage(pageLink.href);
        return;
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

    if (window.localStorage?.getItem('croissantly-fullscreen-mode') === '1') {
        document.body.classList.add('is-fullscreen');
    }

    updateFullscreenToggle();

    document.querySelectorAll('[data-auto-open-modal]').forEach((dialog) => {
        if (dialog instanceof HTMLDialogElement && typeof dialog.showModal === 'function' && !dialog.open) {
            dialog.showModal();
        }
    });
});

document.addEventListener('fullscreenchange', () => {
    const wantsFullscreen = window.localStorage?.getItem('croissantly-fullscreen-mode') === '1';
    document.body.classList.toggle('is-fullscreen', wantsFullscreen || Boolean(document.fullscreenElement));
    updateFullscreenToggle();
});

function updateFullscreenToggle() {
    const wantsFullscreen = window.localStorage?.getItem('croissantly-fullscreen-mode') === '1';
    const fullscreenToggle = document.querySelector('[data-fullscreen-toggle]');
    if (fullscreenToggle) {
        fullscreenToggle.setAttribute('aria-label', wantsFullscreen ? 'Exit fullscreen mode' : 'Enter fullscreen mode');
        fullscreenToggle.setAttribute('title', wantsFullscreen ? 'Exit fullscreen mode' : 'Enter fullscreen mode');
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (shouldSubmitInPlace(form)) {
        event.preventDefault();
        loadPage(new URLSearchParams(new FormData(form)).toString()
            ? `${form.action}?${new URLSearchParams(new FormData(form)).toString()}`
            : form.action);
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

window.addEventListener('popstate', () => {
    if (window.localStorage?.getItem('croissantly-fullscreen-mode') === '1') {
        loadPage(window.location.href, { push: false });
    }
});

function shouldLoadInPlace(link) {
    if (!(link instanceof HTMLAnchorElement)) {
        return false;
    }

    if (window.localStorage?.getItem('croissantly-fullscreen-mode') !== '1') {
        return false;
    }

    if (link.target || link.hasAttribute('download') || link.href.includes('#')) {
        return false;
    }

    const url = new URL(link.href);
    return url.origin === window.location.origin;
}

function shouldSubmitInPlace(form) {
    if (!(form instanceof HTMLFormElement)) {
        return false;
    }

    if (window.localStorage?.getItem('croissantly-fullscreen-mode') !== '1') {
        return false;
    }

    const method = (form.method || 'get').toLowerCase();
    const url = new URL(form.action);

    return method === 'get' && url.origin === window.location.origin;
}

async function loadPage(url, options = {}) {
    const { push = true } = options;
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        window.location.href = url;
        return;
    }

    const html = await response.text();
    const finalUrl = response.url || url;
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const nextMain = nextDocument.querySelector('.main-panel');
    const currentMain = document.querySelector('.main-panel');

    if (!nextMain || !currentMain) {
        window.location.href = url;
        return;
    }

    currentMain.innerHTML = nextMain.innerHTML;

    const nextNav = nextDocument.querySelector('.nav-list');
    const currentNav = document.querySelector('.nav-list');
    if (nextNav && currentNav) {
        currentNav.innerHTML = nextNav.innerHTML;
    }

    const nextTitle = nextDocument.querySelector('title');
    if (nextTitle) {
        document.title = nextTitle.textContent;
    }

    document.body.classList.add('is-fullscreen');
    updateFullscreenToggle();

    if (push && finalUrl !== window.location.href) {
        window.history.pushState({}, '', finalUrl);
    }

    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
}
