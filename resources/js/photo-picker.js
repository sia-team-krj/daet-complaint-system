const initPhotoPickers = (root) => {
    (root || document).querySelectorAll('[data-photo-picker]').forEach((picker) => {
        if (picker.dataset.photoPickerReady === '1') return;
        picker.dataset.photoPickerReady = '1';
        const input = picker.querySelector('[data-photo-input]');
        const trigger = picker.querySelector('[data-photo-trigger]');
        const previews = picker.querySelector('[data-photo-previews]');
        const count = picker.querySelector('[data-photo-count]');
        const errorBox = picker.querySelector('[data-photo-error]');
        if (!input || !previews) return;

        const max = Number(picker.dataset.max || 5);
        const maxBytes = Number(picker.dataset.maxBytes || 5242880);
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const allowedName = /\.(jpe?g|png|webp)$/i;
        const tpl = {
            count: picker.dataset.msgCount || '__count__ / __max__ photos',
            remove: picker.dataset.msgRemove || 'Remove __name__',
            preview: picker.dataset.msgPreview || 'Selected photo: __name__',
            tooMany: picker.dataset.msgTooMany || 'You can attach up to __max__ photos.',
            invalid: picker.dataset.msgInvalid || 'Use JPG, PNG, or WEBP photos no larger than 5 MB each.',
        };

        let selected = [];
        let previewUrls = [];

        const fill = (template, values) => {
            let out = String(template || '');
            Object.entries(values || {}).forEach(([key, value]) => {
                out = out.split(`:${key}`).join(String(value)).split(`__${key}__`).join(String(value));
            });
            return out;
        };

        const showError = (message) => {
            if (!errorBox) return;
            errorBox.textContent = message || '';
            errorBox.hidden = !message;
        };

        const isAllowed = (file) => file.size > 0 && file.size <= maxBytes
            && (allowedTypes.includes(file.type) || allowedName.test(file.name));

        const syncInput = () => {
            if (typeof DataTransfer === 'undefined') return false;
            try {
                const transfer = new DataTransfer();
                selected.forEach(({ file }) => transfer.items.add(file));
                input.files = transfer.files;
                return true;
            } catch (error) {
                return false;
            }
        };

        const render = () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
            previews.replaceChildren();

            selected.forEach(({ id, file }) => {
                const item = document.createElement('div');
                item.className = 'info-request__photo-item';

                const image = document.createElement('img');
                const url = URL.createObjectURL(file);
                previewUrls.push(url);
                image.src = url;
                image.alt = fill(tpl.preview, { name: file.name });
                item.appendChild(image);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'info-request__photo-remove';
                remove.setAttribute('aria-label', fill(tpl.remove, { name: file.name }));
                remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                remove.addEventListener('click', () => {
                    selected = selected.filter((photo) => photo.id !== id);
                    syncInput();
                    showError('');
                    render();
                });
                item.appendChild(remove);
                previews.appendChild(item);
            });

            if (count) {
                count.textContent = fill(tpl.count, { count: selected.length, max });
            }
        };

        const addFiles = (files) => {
            const incoming = Array.from(files || []);
            if (!incoming.length) return;

            if (incoming.some((file) => !isAllowed(file))) {
                showError(tpl.invalid);
                return;
            }

            const seen = new Set(selected.map(({ file }) => `${file.name}-${file.size}-${file.lastModified}`));
            const unique = incoming.filter((file) => {
                const key = `${file.name}-${file.size}-${file.lastModified}`;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });

            const slots = max - selected.length;
            if (unique.length > slots) {
                showError(fill(tpl.tooMany, { max }));
            } else {
                showError('');
            }

            unique.slice(0, Math.max(slots, 0)).forEach((file, index) => {
                selected.push({ id: `${Date.now()}-${index}-${file.name}-${file.size}`, file });
            });

            syncInput();
            render();
        };

        const cameraInput = picker.querySelector('[data-photo-camera]');
        const cameraTrigger = picker.querySelector('[data-photo-camera-trigger]');

        picker.classList.add('is-enhanced');
        picker.querySelector('[data-photo-field]')?.classList.add('is-enhanced');

        if (trigger) trigger.addEventListener('click', () => input.click());
        if (cameraTrigger && cameraInput) cameraTrigger.addEventListener('click', () => cameraInput.click());
        input.addEventListener('change', () => addFiles(input.files));
        if (cameraInput) cameraInput.addEventListener('change', () => { addFiles(cameraInput.files); cameraInput.value = ''; });
        render();
    });
};

export const initializePhotoPickers = () => initPhotoPickers(document);
