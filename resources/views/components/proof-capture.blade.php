{{--
    Proof capture — the staff photo-proof picker.

    Purpose: let a staff member attach the photograph that proves the work was
    done, and state the office's proof rule *before* they choose a status
    rather than as a validation error after they press send.

    The rule it states comes from the server: StaffController@update refuses a
    move to a status that requires documentation unless a photo is attached.
    The list below is what the office can see; if the two ever drift, the
    server still refuses — the form can only fail to warn, never to allow.

    Markup contract for the controller (resources/js/proof-capture.js):
      [data-proof-capture]   the root
      [data-proof-gallery]   the gallery file input (holds the whole selection)
      [data-proof-camera]    the camera file input
      [data-proof-previews]  where the thumbnails are rendered
      [data-proof-count]     the "2 / 5" counter
      [data-proof-error]     the client-side error line
      [data-proof-trigger]   a button; its value is the id of the input it opens
      [data-proof-note]      the requirement note
      [data-proof-status]    the select that chooses the new status

    Docs: docs/design-system.md §5.36.
--}}
@props([
    'name' => 'documentation_images',
    'cameraName' => 'documentation_camera_photos',
    'max' => 5,
    'requireStatuses' => ['In Progress', 'Resolved'],
    'statusSelect' => 'status',
    'initialStatus' => null,
    'label' => null,
    'hint' => null,
])

@php
    $galleryId = $name.'-proof-gallery';
    $cameraId = $cameraName.'-proof-camera';

    // The statuses that demand a photo, with their translated labels, so the
    // note can name the status in the reader's language.
    $statusLabels = collect($requireStatuses)
        ->mapWithKeys(function (string $value): array {
            $status = \App\Enums\ComplaintStatus::tryFrom($value);

            return [$value => $status?->label() ?? $value];
        })
        ->all();

    $initialNeedsPhoto = $initialStatus !== null
        && in_array($initialStatus, array_keys($statusLabels), true);
@endphp

<div
    class="proof-capture"
    data-proof-capture
    data-max="{{ $max }}"
    data-require-statuses="{{ implode(',', array_keys($statusLabels)) }}"
    data-status-select="{{ $statusSelect }}"
    data-status-labels="{{ json_encode($statusLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    data-title-required="{{ __('staff.proof.required_title') }}"
    data-title-optional="{{ __('staff.proof.optional_title') }}"
    data-message-required="{{ __('staff.proof.required_body') }}"
    data-message-optional="{{ $hint ?? __('staff.proof.optional_body') }}"
    data-message-invalid="{{ __('staff.proof.invalid') }}"
    data-message-too-many="{{ __('staff.proof.too_many') }}"
    data-message-remove="{{ __('staff.proof.remove') }}"
    data-message-preview="{{ __('staff.proof.preview') }}"
>
    <div class="proof-capture__note{{ $initialNeedsPhoto ? ' proof-capture__note--required' : '' }}" data-proof-note>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <p>
            <strong data-proof-note-title>{{ $initialNeedsPhoto ? __('staff.proof.required_title') : __('staff.proof.optional_title') }}</strong>
            <span data-proof-note-body>{{ $initialNeedsPhoto
                ? __('staff.proof.required_body', ['status' => $statusLabels[$initialStatus] ?? $initialStatus])
                : ($hint ?? __('staff.proof.optional_body')) }}</span>
        </p>
    </div>

    <div class="proof-capture__zone">
        <input
            type="file"
            name="{{ $name }}[]"
            id="{{ $galleryId }}"
            accept="image/jpeg,image/png,image/webp"
            multiple
            data-proof-gallery
        >
        <input
            type="file"
            name="{{ $cameraName }}[]"
            id="{{ $cameraId }}"
            accept="image/jpeg,image/png,image/webp"
            capture="environment"
            multiple
            data-proof-camera
        >

        <span class="proof-capture__icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
        </span>

        <strong class="proof-capture__heading">{{ __('staff.proof.choose_title') }}</strong>
        <span class="proof-capture__subheading">{{ __('staff.proof.choose_body') }}</span>

        <div class="proof-capture__actions">
            <button type="button" class="proof-capture__action" data-proof-trigger="{{ $galleryId }}">{{ __('staff.proof.library') }}</button>
            <button type="button" class="proof-capture__action proof-capture__action--camera" data-proof-trigger="{{ $cameraId }}">{{ __('staff.proof.camera') }}</button>
        </div>

        <div class="proof-capture__counter">
            <strong data-proof-count aria-live="polite">0 / {{ $max }}</strong>
            <span>{{ __('staff.proof.attached') }}</span>
        </div>

        <div class="proof-capture__previews" data-proof-previews aria-live="polite"></div>

        <div class="proof-capture__error" data-proof-error role="alert" hidden></div>
    </div>

    @error($name)
        <p class="proof-capture__error">{{ $message }}</p>
    @enderror
    @error($cameraName)
        <p class="proof-capture__error">{{ $message }}</p>
    @enderror
</div>
