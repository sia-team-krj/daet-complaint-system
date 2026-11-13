/**
 * Analytics period navigation (docs/design-system.md §5.43, docs/soft-navigation.md).
 *
 * One page load, then fragments. The period chips and the custom range form
 * write the query string; the server renders the period bar and the content
 * and this script swaps them in. Back, Forward, refresh and a shared link all
 * behave like ordinary URLs. With JavaScript off both controls are ordinary
 * links and a form, so the report still works — it just reloads.
 */
export function initializeAnalytics() {
    document.querySelectorAll('[data-analytics], [data-an-preview]').forEach((root) => {
        if (root.dataset.analyticsBound === 'true' || root.dataset.anPreviewBound === 'true') return;

        const endpoint = root.dataset.endpoint;
        const pagePath = root.dataset.pagePath;
        const isLiveRoot = Boolean(endpoint && pagePath);

        if (isLiveRoot) {
            root.dataset.analyticsBound = 'true';
        } else {
            root.dataset.anPreviewBound = 'true';
        }

        bindInteractiveRoot(root);
        if (!isLiveRoot) return;

        const periodEl = root.querySelector('[data-analytics-period]');
        const contentEl = root.querySelector('[data-analytics-content]');
        const jsonHeaders = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const setBusy = (busy) => {
            root.classList.toggle('is-loading', busy);
            root.setAttribute('aria-busy', busy ? 'true' : 'false');
        };

        async function navigate(url, push = true) {
            const target = new URL(endpoint, window.location.origin);
            target.search = url.search;

            setBusy(true);

            try {
                const response = await fetch(target, { headers: jsonHeaders });
                if (!response.ok) throw new Error('request failed');

                const payload = await response.json();
                if (periodEl && typeof payload.period === 'string') {
                    periodEl.innerHTML = payload.period;
                }
                if (contentEl && typeof payload.content === 'string') {
                    contentEl.innerHTML = payload.content;
                }
                if (push) {
                    history.pushState({ analytics: true }, '', url.toString());
                }
            } catch (error) {
                // Never leave stale fragments under a new URL.
                window.location.href = url.toString();
            } finally {
                setBusy(false);
            }
        }

        root.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const link = event.target.closest('a[href]');
            if (!link || !root.contains(link)) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;

            const url = new URL(link.getAttribute('href'), window.location.origin);
            if (url.origin !== window.location.origin || url.pathname !== pagePath) return;

            event.preventDefault();
            navigate(url);
        });

        root.addEventListener('submit', (event) => {
            const form = event.target.closest('[data-analytics-range]');
            if (!form || !root.contains(form)) return;

            event.preventDefault();

            // getAttribute, not form.action: a control named "action" would
            // shadow the property. There is none today; this keeps it safe.
            const url = new URL(form.getAttribute('action'), window.location.origin);
            const data = new FormData(form);

            data.forEach((value, key) => {
                if (typeof value !== 'string' || value === '') {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, value);
                }
            });

            navigate(url);
        });

        window.addEventListener('popstate', () => {
            if (window.location.pathname !== pagePath) return;
            navigate(new URL(window.location.href), false);
        });
    });
}

const interactiveSelector = '[data-an-chart-band], [data-an-donut-hit], [data-an-bar-hit]';

const numberValue = (value, fractionDigits = 0) => {
    const parsed = Number(value);

    if (!Number.isFinite(parsed)) return String(value ?? '');

    return parsed.toLocaleString(document.documentElement.lang || undefined, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
};

const renderTemplate = (template, values) =>
    Object.entries(values).reduce(
        (result, [key, value]) => result.replaceAll(`:${key}`, String(value)),
        template ?? '',
    );

const resetInteractiveContainer = (container) => {
    if (!container) return;

    container.querySelectorAll('.is-active').forEach((element) => element.classList.remove('is-active'));
    container.classList.remove('has-active');

    const detail = container.querySelector('[data-an-chart-detail], [data-an-donut-detail], [data-an-bars-detail]');
    if (detail) detail.textContent = detail.dataset.placeholder || '';
};

const activateInteractiveTarget = (target) => {
    if (!target) return;

    const chart = target.closest('[data-an-chart]');
    if (chart && target.matches('[data-an-chart-band]')) {
        const detail = chart.querySelector('[data-an-chart-detail]');

        chart.querySelectorAll('[data-an-chart-band]').forEach((band) => {
            band.classList.toggle('is-active', band === target);
        });

        if (detail) {
            detail.textContent = renderTemplate(detail.dataset.template, {
                label: target.dataset.label,
                filed: numberValue(target.dataset.filed),
                resolved: numberValue(target.dataset.resolved),
            });
        }

        return;
    }

    const donut = target.closest('[data-an-donut]');
    if (donut && target.matches('[data-an-donut-hit]')) {
        const detail = donut.querySelector('[data-an-donut-detail]');
        const key = target.dataset.key;

        donut.classList.add('has-active');
        donut.querySelectorAll('[data-an-donut-hit]').forEach((button) => {
            button.classList.toggle('is-active', button === target);
        });
        donut.querySelectorAll('[data-an-donut-segment]').forEach((segment) => {
            segment.classList.toggle('is-active', segment.dataset.anDonutSegment === key);
        });

        if (detail) {
            detail.textContent = renderTemplate(detail.dataset.template, {
                label: target.dataset.label,
                count: numberValue(target.dataset.count),
                percent: numberValue(target.dataset.percent, 1),
            });
        }

        return;
    }

    const bars = target.closest('[data-an-bars]');
    if (bars && target.matches('[data-an-bar-hit]')) {
        const detail = bars.querySelector('[data-an-bars-detail]');

        bars.querySelectorAll('[data-an-bar-hit]').forEach((button) => {
            button.classList.toggle('is-active', button === target);
        });

        if (detail) {
            detail.textContent = renderTemplate(detail.dataset.template, {
                label: target.dataset.label,
                count: numberValue(target.dataset.count),
                percent: numberValue(target.dataset.percent, 1),
            });
        }
    }
};

const bindInteractiveRoot = (root) => {
    const findTarget = (event) => {
        const target = event.target.closest?.(interactiveSelector);

        return target && root.contains(target) ? target : null;
    };

    const findContainer = (event) => {
        const container = event.target.closest?.('[data-an-chart], [data-an-donut], [data-an-bars]');

        return container && root.contains(container) ? container : null;
    };

    root.addEventListener('pointerover', (event) => {
        const target = findTarget(event);
        if (target) activateInteractiveTarget(target);
    });

    root.addEventListener('focusin', (event) => {
        const target = findTarget(event);
        if (target) activateInteractiveTarget(target);
    });

    root.addEventListener('click', (event) => {
        const target = findTarget(event);
        if (target) activateInteractiveTarget(target);
    });

    root.addEventListener('pointerout', (event) => {
        const container = findContainer(event);
        if (!container) return;

        if (event.relatedTarget instanceof Node && container.contains(event.relatedTarget)) return;
        resetInteractiveContainer(container);
    });

    root.addEventListener('focusout', (event) => {
        const container = findContainer(event);
        if (!container) return;

        if (event.relatedTarget instanceof Node && container.contains(event.relatedTarget)) return;
        resetInteractiveContainer(container);
    });

    root.addEventListener('keydown', (event) => {
        const target = findTarget(event);

        if (target && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            activateInteractiveTarget(target);
            return;
        }

        if (event.key === 'Escape') {
            resetInteractiveContainer(findContainer(event));
        }
    });
};
