/**
 * Resident identity photo capture — /identity/verify.
 *
 * Purpose: the resident-facing government ID + selfie proof. Two ways to
 * provide each required photo (docs/design-system.md §5.58):
 *
 *   - "Take photo" runs the browser camera (MediaDevices getUserMedia), shows
 *     a live preview and captures a still. The captured frame is written into
 *     the zone's file input through the DataTransfer API, so a captured photo
 *     travels through the same server-side workflow as an uploaded file.
 *   - "Upload file" is a real <label for> file control, so the form still
 *     works with JavaScript off.
 *
 * Markup contract (the JS reads the markup, not a function call):
 *   [data-identity-photo]            the zone; its value is the file input's name
 *   ([data-identity-photo="id_front"])
 *   [data-identity-input]             the file input the zone owns
 *   [data-identity-camera-start]     open the camera
 *   [data-identity-camera]           the live-preview panel
 *   [data-identity-camera-video]     the <video>
 *   [data-identity-camera-capture]  capture the frame
 *   [data-identity-camera-cancel]    stop the camera
 *   [data-identity-preview]          the preview panel
 *   [data-identity-preview-image]   the <img>
 *   [data-identity-retake]           redo from the camera
 *   [data-identity-replace]          redo from the file picker
 *   [data-identity-remove]           clear the photo
 *   [data-identity-state]            status/notice line ("Photo ready", errors)
 *
 * Behaviour: camera permission is requested only when the camera button is
 * pressed; the preview plays the (front-facing for the ID, user-facing for the
 * selfie) camera; capture draws the frame to a canvas and stores the blob; the
 * camera is stopped on capture, cancel or teardown. The submit button
 * ([data-identity-submit]) stays disabled until the required information,
 * both photos and the required consents are in.
 *
 * Docs: docs/design-system.md §5.58.
 */

const MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const ALLOWED_NAME = /\.(jpe?g|png|webp)$/i;

const isAllowedPhoto = (file) => Boolean(file)
    && file.size > 0
    && file.size <= MAX_BYTES
    && (ALLOWED_TYPES.includes(file.type) || ALLOWED_NAME.test(file.name));

const readFacing = (photoKey) => String(photoKey || '').includes('selfie') ? 'user' : 'environment';

/**
 * Wire one identity photo zone.
 *
 * @param {HTMLElement} root
 * @returns {{root: HTMLElement, key: string, has: () => boolean}|null}
 */
export const createIdentityPhoto = (root) => {
    if (!root || root.dataset.identityPhotoBound === 'true') {
        return root?.__daetIdentityPhoto ?? null;
    }
    root.dataset.identityPhotoBound = 'true';

    const input = root.querySelector('[data-identity-input]');
    const preview = root.querySelector('[data-identity-preview]');
    const previewImage = root.querySelector('[data-identity-preview-image]');
    const state = root.querySelector('[data-identity-state]');
    const camera = root.querySelector('[data-identity-camera]');
    const video = root.querySelector('[data-identity-camera-video]');
    const startBtn = root.querySelector('[data-identity-camera-start]');
    const captureBtn = root.querySelector('[data-identity-camera-capture]');
    const cancelBtn = root.querySelector('[data-identity-camera-cancel]');
    const retakeBtn = root.querySelector('[data-identity-retake]');
    const replaceBtn = root.querySelector('[data-identity-replace]');
    const removeBtn = root.querySelector('[data-identity-remove]');

    const key = String(root.dataset.identityPhoto || '');
    const readyText = 'Photo ready';

    const setState = (text, isError = false) => {
        if (!state) return;
        state.textContent = text || '';
        state.setAttribute('data-identity-state-error', isError ? 'true' : 'false');
    };

    let objectUrl = null;

    const clearObjectUrl = () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    };

    const showFile = (file) => {
        clearObjectUrl();
        try {
            objectUrl = URL.createObjectURL(file);
        } catch (error) {
            objectUrl = null;
        }
        if (previewImage) {
            previewImage.src = objectUrl || '';
        }
        if (preview) {
            preview.hidden = false;
        }
        setState(readyText);
        // Bring the captured/selected photo into view so the result is obvious.
        preview?.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
    };

    const clearPhoto = () => {
        if (input) input.value = '';
        clearObjectUrl();
        if (preview) preview.hidden = true;
        if (previewImage) previewImage.src = '';
        setState('');
    };

    let stream = null;

    const stopCamera = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
        if (camera) camera.hidden = true;
    };

    const openCamera = async () => {
        if (!camera || !video) return;
        stopCamera();

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setState('Camera unavailable. Upload a photo instead.', true);
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: readFacing(key) },
                audio: false,
            });
            video.srcObject = stream;
            video.muted = true;
            await video.play();
            // Wait for the stream's frames before capture — play() can resolve
            // before the video dimensions are available.
            await new Promise((resolve) => {
                if (video.videoWidth) { resolve(); return; }
                const onReady = () => {
                    video.removeEventListener('loadedmetadata', onReady);
                    video.removeEventListener('canplay', onReady);
                    resolve();
                };
                video.addEventListener('loadedmetadata', onReady);
                video.addEventListener('canplay', onReady);
            });
            if (camera) camera.hidden = false;
            if (state) state.textContent = '';
        } catch (error) {
            stopCamera();
            setState(error?.name === 'NotAllowedError' ? 'Camera permission denied. Upload a photo instead.' : 'Camera unavailable. Upload a photo instead.', true);
        }
    };

    const captureFrame = async () => {
        setState('Capturing…');

        if (!video) {
            setState('Camera unavailable. Upload a photo instead.', true);
            return;
        }

        if (!video.videoWidth || !video.videoHeight) {
            setState('Camera is still starting. Try again in a moment.', true);
            return;
        }

        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            if (!context) {
                setState('Camera unavailable. Upload a photo instead.', true);
                return;
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            let blob = await new Promise((resolve) => {
                try {
                    canvas.toBlob((result) => resolve(result), 'image/jpeg', 0.92);
                } catch (error) {
                    resolve(null);
                }
            });

            // Fallback for environments where toBlob is unreliable.
            if (!blob) {
                try {
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                    const res = await fetch(dataUrl);
                    blob = await res.blob();
                } catch (error) {
                    blob = null;
                }
            }

            if (!blob) {
                setState('Could not capture the photo. Upload a photo instead.', true);
                return;
            }

            const file = new File([blob], `${key}-capture.jpg`, { type: blob.type || 'image/jpeg' });

            if (input && typeof DataTransfer !== 'undefined') {
                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                } catch (error) {
                    // Preview still shows below; the user can re-upload if needed.
                }
            }

            showFile(file);
            stopCamera();
            syncSubmitState();
            setState(readyText);
        } catch (error) {
            stopCamera();
            setState('Could not capture the photo. Upload a photo instead.', true);
        }
    };

    const syncSubmitState = () => {
        const form = root.closest('form');
        if (!form) return;

        const photoFields = ['id_front', 'selfie'];
        const photosReady = photoFields.every((name) => {
            const zone = form.querySelector(`[data-identity-photo="${name}"]`);
            const zoneInput = zone?.querySelector('[data-identity-input]');
            return Boolean(zoneInput && zoneInput.files && zoneInput.files.length > 0);
        });

        const idNumber = form.querySelector('[name="id_number"]');
        const idOk = Boolean(idNumber && idNumber.value && idNumber.value.trim().length > 0);

        const consentNames = ['terms', 'consent_purpose', 'consent_access', 'consent_retention'];
        const consentOk = consentNames.every((name) => {
            const element = form.querySelector(`[name="${name}"]`);
            return Boolean(element) && element.checked === true;
        });

        const submit = form.querySelector('[data-identity-submit]');
        if (submit) {
            submit.disabled = !(photosReady && idOk && consentOk);
        }
    };

    startBtn?.addEventListener('click', () => {
        openCamera();
    });

    captureBtn?.addEventListener('click', () => {
        captureFrame().catch(() => {
            stopCamera();
            setState('Could not capture the photo. Upload a photo instead.', true);
        });
    });

    cancelBtn?.addEventListener('click', () => {
        stopCamera();
        clearPhoto();
    });

    retakeBtn?.addEventListener('click', () => {
        stopCamera();
        clearPhoto();
    });

    replaceBtn?.addEventListener('click', () => {
        clearPhoto();
        if (input) input.click();
    });

    removeBtn?.addEventListener('click', () => {
        clearPhoto();
        syncSubmitState();
    });

    input?.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (file && isAllowedPhoto(file)) {
            showFile(file);
        } else {
            setState('Use JPG, PNG or WebP photos no larger than 5 MB.', true);
        }
        syncSubmitState();
    });

    const form = root.closest('form');
    const bindGating = () => {
        if (!form || form.dataset.identityGatingBound === 'true') return;
        form.dataset.identityGatingBound = 'true';

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (
                target?.matches?.('[name="id_number"], [name="id_type"], [name="terms"], [name="consent_purpose"], [name="consent_access"], [name="consent_retention"]')
            ) {
                syncSubmitState();
            }
        });

        form.addEventListener('input', (event) => {
            const target = event.target;
            if (target?.matches?.('[name="id_number"], [name="id_type"]')) {
                syncSubmitState();
            }
        });
    };

    bindGating();
    syncSubmitState();

    const controller = {
        root,
        key,
        has: () => Boolean(input && input.files && input.files.length > 0),
    };

    root.__daetIdentityPhoto = controller;

    return controller;
};

/**
 * Wire every resident identity photo zone on the page.
 *
 * @param {ParentNode} scope
 * @returns {number} how many were wired
 */
export const initializeIdentityPhotos = (scope = document) => {
    const roots = Array.from(scope.querySelectorAll('[data-identity-photo]'));
    roots.forEach((root) => createIdentityPhoto(root));
    return roots.length;
};
