(() => {
    if (window.__daetShellControllerBound === true) {
        window.__daetShellInitialize?.();
        return;
    }

    window.__daetShellControllerBound = true;

    const setMobileMenu = (button, isOpen) => {
        const menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) return;

        menu.classList.toggle('is-open', isOpen);
        menu.setAttribute('aria-hidden', String(!isOpen));
        menu.toggleAttribute('inert', !isOpen);
        button.setAttribute('aria-expanded', String(isOpen));
        button.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');

        const hasOpenMobileMenu = [...document.querySelectorAll('[data-mobile-menu].is-open')].length > 0;
        document.documentElement.classList.toggle('mobile-menu-open', hasOpenMobileMenu);
        document.body.classList.toggle('mobile-menu-open', hasOpenMobileMenu);
    };

    const setUserMenu = (button, isOpen) => {
        const dropdown = document.getElementById(button.getAttribute('aria-controls'));
        if (!dropdown) return;

        dropdown.classList.toggle('open', isOpen);
        dropdown.setAttribute('aria-hidden', String(!isOpen));
        button.setAttribute('aria-expanded', String(isOpen));
        button.setAttribute('aria-label', isOpen ? 'Close account menu' : 'Open account menu');
    };

    // The bell reuses the account dropdown's open/close contract so the two
    // popovers behave identically and can never both be open at once.
    const setNotificationMenu = (button, isOpen) => {
        const dropdown = document.getElementById(button.getAttribute('aria-controls'));
        if (!dropdown) return;

        dropdown.classList.toggle('open', isOpen);
        dropdown.setAttribute('aria-hidden', String(!isOpen));
        button.setAttribute('aria-expanded', String(isOpen));
    };

    const closeNotificationMenus = (target = null) => {
        document.querySelectorAll('[data-site-navbar]').forEach((navbar) => {
            navbar.querySelectorAll('[data-notification-toggle]').forEach((bell) => {
                const dropdown = document.getElementById(bell.getAttribute('aria-controls'));
                if (dropdown?.classList.contains('open') && !dropdown.contains(target)) {
                    setNotificationMenu(bell, false);
                }
            });
        });
    };

    const setSidebar = (sidebar, isOpen) => {
        if (!sidebar) return;

        const isStaff = sidebar.id === 'staff-sidebar';
        const toggleAttribute = isStaff ? 'data-staff-sidebar-toggle' : 'data-admin-sidebar-toggle';
        const bodyClass = isStaff ? 'staff-sidebar-open' : 'admin-sidebar-open';

        sidebar.classList.toggle('is-open', isOpen);
        document.querySelectorAll(`[${toggleAttribute}]`).forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
        document.documentElement.classList.toggle(bodyClass, isOpen);
        document.body.classList.toggle(bodyClass, isOpen);
    };

    const closeUserMenus = (target = null) => {
        document.querySelectorAll('[data-site-navbar]').forEach((navbar) => {
            navbar.querySelectorAll('[data-user-menu-toggle]').forEach((userButton) => {
                const dropdown = document.getElementById(userButton.getAttribute('aria-controls'));
                if (dropdown?.classList.contains('open') && !dropdown.contains(target)) {
                    setUserMenu(userButton, false);
                }
            });
        });
    };

    const closeGlobalMenus = (target = null) => {
        document.querySelectorAll('[data-site-navbar]').forEach((navbar) => {
            const mobileButton = navbar.querySelector('[data-mobile-menu-toggle]');
            const menu = navbar.querySelector('[data-mobile-menu]');

            if (mobileButton && menu && menu.classList.contains('is-open') && !menu.contains(target)) {
                setMobileMenu(mobileButton, false);
            }
        });

        closeUserMenus(target);
        closeNotificationMenus(target);
    };

    const closeSidebar = (sidebar) => setSidebar(sidebar, false);

    const labelTableCells = () => {
        document.querySelectorAll('table').forEach((table) => {
            const headers = Array.from(table.querySelectorAll('thead th')).map((header, index) => {
                return header.textContent.trim() || `Column ${index + 1}`;
            });

            table.querySelectorAll('tbody tr').forEach((row) => {
                Array.from(row.children).forEach((cell, index) => {
                    if (cell.tagName !== 'TD') return;
                    cell.dataset.label = cell.dataset.label || headers[index] || 'Detail';
                });
            });
        });
    };

    const resetShell = () => {
        document.querySelectorAll('[data-site-navbar]').forEach((navbar) => {
            const mobileButton = navbar.querySelector('[data-mobile-menu-toggle]');
            const menu = navbar.querySelector('[data-mobile-menu]');

            if (mobileButton && menu) setMobileMenu(mobileButton, false);
            navbar.querySelectorAll('[data-user-menu-toggle]').forEach((userButton) => {
                setUserMenu(userButton, false);
            });
            navbar.querySelectorAll('[data-notification-toggle]').forEach((bell) => {
                setNotificationMenu(bell, false);
            });
        });

        closeSidebar(document.getElementById('staff-sidebar'));
        closeSidebar(document.getElementById('admin-sidebar'));
        document.documentElement.classList.remove('staff-sidebar-open', 'admin-sidebar-open');
        document.body.classList.remove('staff-sidebar-open', 'admin-sidebar-open');
        labelTableCells();
    };

    const handleClick = (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        const mobileButton = target.closest('[data-mobile-menu-toggle]');
        if (mobileButton) {
            event.preventDefault();
            event.stopPropagation();
            const menu = document.getElementById(mobileButton.getAttribute('aria-controls'));
            closeUserMenus();
            // The bell branch below already does this for the menu; without the
            // mirror here the drawer opened on top of a live notification panel
            // and left both showing at once. One surface open, both directions.
            closeNotificationMenus();
            if (menu) setMobileMenu(mobileButton, !menu.classList.contains('is-open'));
            return;
        }

        const userButton = target.closest('[data-user-menu-toggle]');
        if (userButton) {
            event.preventDefault();
            event.stopPropagation();
            const dropdown = document.getElementById(userButton.getAttribute('aria-controls'));
            const navbar = userButton.closest('[data-site-navbar]');
            const mobileButton = navbar?.querySelector('[data-mobile-menu-toggle]');
            closeNotificationMenus();
            closeSidebar(document.getElementById('staff-sidebar'));
            closeSidebar(document.getElementById('admin-sidebar'));
            if (mobileButton) setMobileMenu(mobileButton, false);
            if (dropdown) setUserMenu(userButton, !dropdown.classList.contains('open'));
            return;
        }

        const notificationButton = target.closest('[data-notification-toggle]');
        if (notificationButton) {
            event.preventDefault();
            event.stopPropagation();
            const dropdown = document.getElementById(notificationButton.getAttribute('aria-controls'));
            const navbar = notificationButton.closest('[data-site-navbar]');
            const mobileButton = navbar?.querySelector('[data-mobile-menu-toggle]');
            closeUserMenus();
            closeSidebar(document.getElementById('staff-sidebar'));
            closeSidebar(document.getElementById('admin-sidebar'));
            if (mobileButton) setMobileMenu(mobileButton, false);
            if (dropdown) setNotificationMenu(notificationButton, !dropdown.classList.contains('open'));
            return;
        }

        const staffToggle = target.closest('[data-staff-sidebar-toggle]');
        if (staffToggle) {
            event.preventDefault();
            closeUserMenus();
            closeNotificationMenus();
            const sidebar = document.getElementById('staff-sidebar');
            if (sidebar) setSidebar(sidebar, !sidebar.classList.contains('is-open'));
            return;
        }

        const adminToggle = target.closest('[data-admin-sidebar-toggle]');
        if (adminToggle) {
            event.preventDefault();
            closeUserMenus();
            closeNotificationMenus();
            const sidebar = document.getElementById('admin-sidebar');
            if (sidebar) setSidebar(sidebar, !sidebar.classList.contains('is-open'));
            return;
        }

        closeGlobalMenus(target);

        const mobileLink = target.closest('[data-mobile-menu] a');
        if (mobileLink) {
            const button = mobileLink.closest('[data-site-navbar]')?.querySelector('[data-mobile-menu-toggle]');
            if (button) setMobileMenu(button, false);
        }

        const staffLink = target.closest('#staff-sidebar a');
        const adminLink = target.closest('#admin-sidebar a');
        if ((staffLink || adminLink) && window.matchMedia('(max-width: 980px)').matches) {
            closeSidebar(staffLink ? document.getElementById('staff-sidebar') : document.getElementById('admin-sidebar'));
        }
    };

    const handleKeydown = (event) => {
        if (event.key !== 'Escape') return;
        resetShell();
    };

    const handleResize = () => {
        closeUserMenus();
        closeNotificationMenus();

        if (window.innerWidth > 768) {
            document.querySelectorAll('[data-site-navbar]').forEach((navbar) => {
                const mobileButton = navbar.querySelector('[data-mobile-menu-toggle]');
                const menu = navbar.querySelector('[data-mobile-menu]');
                if (mobileButton && menu && menu.classList.contains('is-open')) {
                    setMobileMenu(mobileButton, false);
                }
            });
        }

        if (window.innerWidth > 980) {
            closeSidebar(document.getElementById('staff-sidebar'));
            closeSidebar(document.getElementById('admin-sidebar'));
        }
    };

    window.__daetShellInitialize = resetShell;
    document.addEventListener('click', handleClick);
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', handleResize);
    document.addEventListener('livewire:navigated', resetShell);
    window.addEventListener('pageshow', resetShell);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resetShell);
    } else {
        resetShell();
    }
})();
