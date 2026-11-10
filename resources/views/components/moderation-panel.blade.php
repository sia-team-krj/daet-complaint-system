@props([
    'complaint',
    'action',
    'idPrefix' => 'moderation',
])

{{--
    Public-presentation moderation, shared by the staff and admin ticket pages.

    The office may withhold or redact, and must give a reason. It may never
    publish or unpublish: that decision belongs to the resident alone. One
    markup for both surfaces so the rule cannot drift between them; `action`
    carries the route the current page owns.

    The wrapper class is `moderation-panel`; a light surface adds
    `moderation-panel--light`. Docs: docs/design-system.md §5.40.
--}}
<div {{ $attributes->merge(['class' => 'moderation-panel']) }}>
    <h3 class="moderation-panel__title">{{ __('staff.moderation.title') }}</h3>
    <p class="moderation-panel__lede">{{ __('staff.moderation.lede') }}</p>

    <p class="moderation-panel__consent">
        <strong>{{ $complaint->is_public ? __('staff.moderation.published') : __('staff.moderation.private') }}</strong>
        {{ $complaint->is_public
            ? __('staff.moderation.published_body')
            : __('staff.moderation.private_body') }}
    </p>

    <form data-loading-submit method="POST" action="{{ $action }}">
        @csrf

        <div class="moderation-field">
            <label class="moderation-check" for="{{ $idPrefix }}-moderation-hide">
                <input type="hidden" name="hidden_from_public" value="0">
                <input type="checkbox" id="{{ $idPrefix }}-moderation-hide" name="hidden_from_public" value="1" @checked($complaint->hidden_from_public)>
                <span class="moderation-check__text">{{ __('staff.moderation.hide') }}</span>
            </label>
        </div>

        <div class="moderation-field">
            <label for="{{ $idPrefix }}-redacted_title">{{ __('staff.moderation.redacted_title') }}</label>
            <input type="text" id="{{ $idPrefix }}-redacted_title" name="redacted_title" maxlength="255"
                   value="{{ old('redacted_title', $complaint->redacted_title) }}"
                   placeholder="{{ $complaint->title }}">
        </div>

        <div class="moderation-field">
            <label for="{{ $idPrefix }}-redacted_description">{{ __('staff.moderation.redacted_description') }}</label>
            <textarea id="{{ $idPrefix }}-redacted_description" name="redacted_description" rows="4" maxlength="5000"
                      placeholder="{{ $complaint->description }}">{{ old('redacted_description', $complaint->redacted_description) }}</textarea>
        </div>

        @if (count($complaint->allImagePaths()) > 0)
            <div class="moderation-field">
                <label>{{ __('staff.moderation.photos') }}</label>
                <div class="moderation-photos">
                    @foreach ($complaint->allImagePaths() as $path)
                        @php $isWithheld = in_array($path, $complaint->withheldImagePaths(), true); @endphp
                        <div class="moderation-photo">
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ basename($path) }}</span>
                            @if ($isWithheld)
                                <label style="margin: 0; color: var(--color-gold); cursor: pointer;">
                                    <input type="checkbox" name="restore_image_paths[]" value="{{ $path }}"> {{ __('staff.moderation.restore') }}
                                </label>
                            @else
                                <label style="margin: 0; cursor: pointer;">
                                    <input type="checkbox" name="withheld_image_paths[]" value="{{ $path }}"> {{ __('staff.moderation.withhold') }}
                                </label>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="moderation-field">
            <label for="{{ $idPrefix }}-moderation_reason">{{ __('staff.moderation.reason') }}</label>
            <textarea id="{{ $idPrefix }}-moderation_reason" name="moderation_reason" rows="2" maxlength="1000"
                      placeholder="{{ __('staff.moderation.reason_placeholder') }}">{{ old('moderation_reason', $complaint->moderation_reason) }}</textarea>
        </div>

        @error('moderation_reason') <div class="filing-error">{{ $message }}</div> @enderror

        <button type="submit" class="moderation-panel__submit">{{ __('staff.moderation.save') }}</button>
    </form>
</div>
