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

    // The badge and feed are rendered server-side, then kept current by a light
    // poll. Without this the bell only changes on a full page load, so a
    // notification that arrived a minute ago looks like it never arrived.
    const notificationBells = () => [...document.querySelectorAll('[data-notification-toggle]')];

    const setNotificationBadge = (bell, badgeText, unreadCount) => {
        let badge = bell.querySelector('[data-notification-badge]');

        // Older cached markup may not carry the span; create it rather than
        // requiring a hard refresh after deploying this controller.
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'nav-bell__badge';
            badge.setAttribute('data-notification-badge', '');
            bell.appendChild(badge);
        }

        const hasUnread = unreadCount > 0;
        badge.hidden = !hasUnread;
        badge.textContent = hasUnread ? badgeText : '';
        bell.setAttribute(
            'aria-label',
            hasUnread ? `${badgeText} unread notifications` : 'Notifications',
        );
    };

    const refreshNotificationBadge = async () => {
        const bells = notificationBells();

        if (
            bells.length === 0
            || typeof window.fetch !== 'function'
            || document.visibilityState === 'hidden'
        ) {
            return;
        }

        const url = bells[0].dataset.notificationCountUrl || '/notifications/unread-count';

        try {
            const response = await window.fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok || response.redirected) return;

            const data = await response.json();
            const badgeText = String(data.badge ?? '');
            const unreadCount = Number(data.unread ?? 0);

            bells.forEach((bell) => setNotificationBadge(bell, badgeText, unreadCount));
        } catch {
            // A transient poll failure is not worth breaking the shell over.
        }
    };

    const refreshNotificationFeed = async (bell) => {
        if (typeof window.fetch !== 'function') return;

        const dropdown = document.getElementById(bell.getAttribute('aria-controls'));
        if (!dropdown) return;

        const url = bell.dataset.notificationFeedUrl || '/notifications/feed';

        try {
            const response = await window.fetch(url, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const markup = await response.text();

            // The reader may have closed it while the request was in flight.
            if (dropdown.classList.contains('open')) {
                dropdown.innerHTML = markup;
            }
        } catch {
            // Keep the server-rendered rows if the refresh fails.
        }
    };

    const startNotificationPolling = () => {
        if (window.__daetNotificationPollingStarted === true) return;

        const bells = notificationBells();
        if (bells.length === 0) return;

        window.__daetNotificationPollingStarted = true;
        refreshNotificationBadge();
        window.setInterval(refreshNotificationBadge, 30000);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') refreshNotificationBadge();
        });
        window.addEventListener('focus', refreshNotificationBadge);
        // Back/forward cache restores keep the old DOM, so the badge must be
        // reconciled with the server rather than trusting what was frozen.
        window.addEventListener('pageshow', refreshNotificationBadge);
    };

    const setSidebar = (sidebar, isOpen) => {
        if (!sidebar) return;

        const isStaff = sidebar.id === 'staff-sidebar';
        const toggleAttribute = isStaff ? 'data-staff-sidebar-toggle' : 'data-admin-sidebar-toggle';
        const bodyClass = isStaff ? 'staff-sidebar-open' : 'admin-sidebar-open';

        if (isOpen) {
            const active = document.activeElement;
            sidebar.__toggleTrigger = active instanceof HTMLElement ? active : null;
        }

        sidebar.classList.toggle('is-open', isOpen);
        document.querySelectorAll(`[${toggleAttribute}]`).forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
        document.documentElement.classList.toggle(bodyClass, isOpen);
        document.body.classList.toggle(bodyClass, isOpen);

        if (isOpen) {
            const first = sidebar.querySelector('button:not([tabindex="-1"]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (first instanceof HTMLElement) first.focus();
        } else if (sidebar.__toggleTrigger instanceof HTMLElement && document.contains(sidebar.__toggleTrigger)) {
            sidebar.__toggleTrigger.focus();
            sidebar.__toggleTrigger = null;
        }
    };

    const setMobileDrawer = (isOpen) => {
        const drawer = document.getElementById('mobile-nav-drawer');
        const overlay = document.getElementById('mobile-nav-overlay');
        if (!drawer) return;

        if (isOpen) {
            const active = document.activeElement;
            drawer.__toggleTrigger = active instanceof HTMLElement ? active : null;
        }

        drawer.classList.toggle('is-open', isOpen);
        drawer.setAttribute('aria-hidden', String(!isOpen));
        if (isOpen) drawer.removeAttribute('inert');
        else drawer.setAttribute('inert', '');

        if (overlay) {
            overlay.classList.toggle('is-open', isOpen);
            overlay.setAttribute('aria-hidden', String(!isOpen));
        }

        document.querySelectorAll('[data-mobile-drawer-toggle]').forEach((toggle) => {
            if (toggle.id === 'mobile-nav-overlay') return;
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        });

        document.documentElement.classList.toggle('mobile-drawer-open', isOpen);
        document.body.classList.toggle('mobile-drawer-open', isOpen);

        if (isOpen) {
            const first = drawer.querySelector('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (first instanceof HTMLElement) first.focus();
        } else if (drawer.__toggleTrigger instanceof HTMLElement && document.contains(drawer.__toggleTrigger)) {
            drawer.__toggleTrigger.focus();
            drawer.__toggleTrigger = null;
        }
    };

    const closeMobileDrawer = () => setMobileDrawer(false);

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
        closeMobileDrawer();
        document.documentElement.classList.remove('staff-sidebar-open', 'admin-sidebar-open', 'mobile-drawer-open');
        document.body.classList.remove('staff-sidebar-open', 'admin-sidebar-open', 'mobile-drawer-open');
        labelTableCells();
        startNotificationPolling();
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

            if (dropdown) {
                const willOpen = !dropdown.classList.contains('open');
                setNotificationMenu(notificationButton, willOpen);

                if (willOpen) {
                    refreshNotificationBadge();
                    refreshNotificationFeed(notificationButton);
                }
            }

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
            closeMobileDrawer();
            const sidebar = document.getElementById('admin-sidebar');
            if (sidebar) setSidebar(sidebar, !sidebar.classList.contains('is-open'));
            return;
        }

        const drawerToggle = target.closest('[data-mobile-drawer-toggle]');
        if (drawerToggle) {
            event.preventDefault();
            closeUserMenus();
            closeNotificationMenus();
            closeSidebar(document.getElementById('staff-sidebar'));
            closeSidebar(document.getElementById('admin-sidebar'));
            const drawer = document.getElementById('mobile-nav-drawer');
            if (drawer) setMobileDrawer(!drawer.classList.contains('is-open'));
            return;
        }

        closeGlobalMenus(target);

        const drawerLink = target.closest('#mobile-nav-drawer a, #mobile-nav-drawer button[type="submit"]');
        if (drawerLink && drawerLink.closest('#mobile-nav-drawer')) {
            closeMobileDrawer();
        }

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
        if (event.key === 'Escape') {
            resetShell();
            return;
        }

        if (event.key === 'Tab') {
            const drawer = document.getElementById('mobile-nav-drawer');
            if (!drawer || !drawer.classList.contains('is-open')) return;
            const focusable = [...drawer.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter((el) => !el.hasAttribute('disabled') && el.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
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

        if (window.innerWidth > 640) {
            closeMobileDrawer();
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
