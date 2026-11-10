/**
 * Proof capture — the staff-side photo-proof picker.
 *
 * Staff-only. The resident filing wizard has its own picker and is not
 * touched by this file.
 *
 * Two jobs:
 *
 * 1. Collect the photos and write them back into the form. A file input's own
 *    list cannot be edited, so per-photo removal is only possible if the
 *    picker owns the selection and writes it back through the DataTransfer
 *    API. The camera input is emptied only after the write-back succeeds, so
 *    a browser that refuses DataTransfer still submits the camera photos —
 *    the server reads both inputs and merges them.
 *
 * 2. State the rule. The server refuses a move to a status that requires
 *    documentation unless a photo is attached (StaffController@update). The
 *    form says so as soon as the staff member picks that status, instead of
 *    after they press send.
 *
 * The contract is the markup:
 *
 *   [data-proof-capture]   the root; carries data-max, the statuses that
 *                          require a photo, the status select's id and the
 *                          message strings
 *   [data-proof-gallery]   the gallery file input (holds the whole selection)
 *   [data-proof-camera]    the camera file input
 *   [data-proof-previews]  where the thumbnails are rendered
 *   [data-proof-count]     the "2 / 5" counter
 *   [data-proof-error]     the client-side error line
 *   [data-proof-trigger]   a button; its value is the id of the input it opens
 *   [data-proof-note]      the requirement note
 *   [data-proof-status]    the select that chooses the new status
 *
 * Without JavaScript the form is still a working form: the browser puts the
 * files in the inputs, the server reads them, and what is lost is the preview,
 * the per-photo remove button and the live rule.
 *
 * Docs: docs/design-system.md §5.36.
 */

const DEFAULT_MAX = 5;
const DEFAULT_MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const ALLOWED_NAME = /\.(jpe?g|png|webp)$/i;

const FALLBACK_MESSAGES = {
    invalid: 'Use JPG, PNG, or WEBP photos no larger than 5 MB each.',
    tooMany: 'You can attach a maximum of :max photos.',
    remove: 'Remove :name',
    preview: 'Selected documentation photo :name',
};

const fill = (template, values) => {
    let out = String(template ?? '');
    Object.entries(values || {}).forEach(([key, value]) => {
        // Laravel's placeholder convention (:key), so a string filled in the
        // browser reads the same as one filled by __().
        out = out.split(`:${key}`).join(String(value));
    });
    return out;
};

const readJson = (raw, fallback) => {
    try {
        const parsed = JSON.parse(raw || 'null');
        return parsed && typeof parsed === 'object' ? parsed : fallback;
    } catch (error) {
        return fallback;
    }
};

/**
 * Wire one proof capture.
 *
 * @param {HTMLElement} root
 * @returns {{add: (files: FileList|File[]) => void, count: () => number}|null}
 */
export const createProofCapture = (root) => {
    if (!root || root.dataset.proofBound === 'true') {
        return root?.__daetProofCapture ?? null;
    }
    root.dataset.proofBound = 'true';

    const gallery = root.querySelector('[data-proof-gallery]');
    const camera = root.querySelector('[data-proof-camera]');
    const previews = root.querySelector('[data-proof-previews]');
    const counter = root.querySelector('[data-proof-count]');
    const errorBox = root.querySelector('[data-proof-error]');
    const note = root.querySelector('[data-proof-note]');
    const noteTitle = root.querySelector('[data-proof-note-title]');
    const noteBody = root.querySelector('[data-proof-note-body]');

    const statusSelect = root.dataset.statusSelect
        ? document.getElementById(root.dataset.statusSelect)
        : null;

    const max = Number(root.dataset.max || DEFAULT_MAX);
    const maxBytes = Number(root.dataset.maxBytes || DEFAULT_MAX_BYTES);
    const requiredStatuses = String(root.dataset.requireStatuses || '')
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);
    const statusLabels = readJson(root.dataset.statusLabels, {});
    const messages = {
        invalid: root.dataset.messageInvalid || FALLBACK_MESSAGES.invalid,
        tooMany: root.dataset.messageTooMany || FALLBACK_MESSAGES.tooMany,
        remove: root.dataset.messageRemove || FALLBACK_MESSAGES.remove,
        preview: root.dataset.messagePreview || FALLBACK_MESSAGES.preview,
        required: root.dataset.messageRequired || '',
        optional: root.dataset.messageOptional || '',
    };

    let selected = [];
    let previewUrls = [];

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = !message;
    };

    const isAllowedPhoto = (file) => {
        const allowedType = ALLOWED_TYPES.includes(file.type);
        const allowedName = ALLOWED_NAME.test(file.name);
        return file.size > 0 && file.size <= maxBytes && (allowedType || allowedName);
    };

    const syncGalleryInput = () => {
        if (!gallery || typeof DataTransfer === 'undefined') return false;
        try {
            const transfer = new DataTransfer();
            selected.forEach(({ file }) => transfer.items.add(file));
            gallery.files = transfer.files;
            return true;
        } catch (error) {
            // Safari refuses DataTransfer on a file input in some versions. The
            // photos the staff member picked are still in the input, so the
            // form still submits them — only the preview list and the
            // per-photo remove button are lost.
            return false;
        }
    };

    /**
     * Say whether the chosen status demands a photo, and why.
     */
    const updateRequirement = () => {
        if (!note) return;
        const current = statusSelect?.value || '';
        const needsPhoto = requiredStatuses.includes(current);

        note.classList.toggle('proof-capture__note--required', needsPhoto);

        if (noteTitle) {
            noteTitle.textContent = needsPhoto
                ? (root.dataset.titleRequired || noteTitle.textContent)
                : (root.dataset.titleOptional || noteTitle.textContent);
        }

        if (noteBody) {
            noteBody.textContent = needsPhoto
                ? fill(messages.required, { status: statusLabels[current] || current })
                : messages.optional;
        }
    };

    const render = () => {
        if (previews) {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
            previews.replaceChildren();

            selected.forEach(({ id, file }) => {
                const preview = document.createElement('div');
                preview.className = 'proof-capture__preview';

                const image = document.createElement('img');
                const objectUrl = URL.createObjectURL(file);
                previewUrls.push(objectUrl);
                image.src = objectUrl;
                image.alt = fill(messages.preview, { name: file.name });
                preview.appendChild(image);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'proof-capture__preview-remove';
                remove.setAttribute('aria-label', fill(messages.remove, { name: file.name }));
                remove.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                remove.addEventListener('click', () => {
                    selected = selected.filter((photo) => photo.id !== id);
                    syncGalleryInput();
                    showError('');
                    render();
                });
                preview.appendChild(remove);

                previews.appendChild(preview);
            });
        }

        if (counter) {
            counter.textContent = `${selected.length} / ${max}`;
        }

        root.dispatchEvent(new CustomEvent('proof:change', {
            bubbles: true,
            detail: { count: selected.length, max },
        }));
    };

    const add = (files) => {
        const incoming = Array.from(files || []);
        const invalid = incoming.find((file) => !isAllowedPhoto(file));
        if (invalid) {
            showError(messages.invalid);
            return;
        }

        // The same photo picked twice (from the library and the camera) is one
        // photo, not two.
        const seen = new Set(selected.map(({ file }) => `${file.name}-${file.size}-${file.lastModified}`));
        const unique = incoming.filter((file) => {
            const key = `${file.name}-${file.size}-${file.lastModified}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });

        const slots = max - selected.length;
        if (unique.length > slots) {
            showError(fill(messages.tooMany, { max }));
        } else {
            showError('');
        }

        unique.slice(0, Math.max(slots, 0)).forEach((file, index) => {
            selected.push({ id: `${Date.now()}-${index}-${file.name}-${file.size}`, file });
        });

        syncGalleryInput();
        render();
    };

    root.querySelectorAll('[data-proof-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const input = document.getElementById(trigger.dataset.proofTrigger);
            input?.click();
        });
    });

    gallery?.addEventListener('change', () => add(gallery.files));

    camera?.addEventListener('change', () => {
        if (!camera.files?.length) return;
        add(camera.files);
        // Only empty the camera input once the gallery input carries the
        // selection — otherwise the server would never see these photos.
        if (syncGalleryInput()) camera.value = '';
    });

    const zone = root.querySelector('.proof-capture__zone') || root;

    zone.addEventListener('click', (event) => {
        if (event.target === gallery || event.target === camera) return;
        if (event.target.closest('button')) return;
        gallery?.click();
    });

    zone.addEventListener('dragover', (event) => {
        event.preventDefault();
        zone.classList.add('is-dragging');
    });

    zone.addEventListener('dragleave', () => zone.classList.remove('is-dragging'));

    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('is-dragging');
        add(event.dataTransfer?.files);
    });

    statusSelect?.addEventListener('change', updateRequirement);
    updateRequirement();
    render();

    const controller = {
        add,
        count: () => selected.length,
    };

    root.__daetProofCapture = controller;

    return controller;
};

/**
 * Wire every proof capture on the page.
 *
 * @param {ParentNode} scope
 * @returns {number} how many were wired
 */
export const initializeProofCaptures = (scope = document) => {
    const captures = scope.querySelectorAll('[data-proof-capture]');
    captures.forEach((capture) => createProofCapture(capture));
    return captures.length;
};
