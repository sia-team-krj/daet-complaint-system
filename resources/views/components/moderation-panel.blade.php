@props([
    'complaint',
    'action',
    'idPrefix' => 'moderation',
    'quickRedactAction' => null,
])

{{--
    Public-presentation moderation, shared by the staff and admin ticket pages.

    The office may withhold or redact, and must give a reason. It may never
    publish or unpublish: that decision belongs to the resident alone.

    Quick redaction (privacy-gated draft helper) is a separate action/form from
    saving moderation so the two can never nest or submit to the wrong route.
--}}
@php
    $canQuickRedact = $quickRedactAction && $complaint->isPrivacyGated() && ($complaint->namedParty() || $complaint->namedPartyPosition());
    $allImages = $complaint->allImagePaths();
@endphp

<div {{ $attributes->merge(['class' => 'moderation-panel']) }}>
    <div class="moderation-panel__header">
        <h3 class="moderation-panel__title">{{ __('staff.moderation.title') }}</h3>
        <p class="moderation-panel__lede">{{ __('staff.moderation.lede') }}</p>
    </div>

    <p class="moderation-panel__consent">
        <strong>{{ $complaint->is_public ? __('staff.moderation.published') : __('staff.moderation.private') }}</strong>
        {{ $complaint->is_public
            ? __('staff.moderation.published_body')
            : __('staff.moderation.private_body') }}
    </p>

    @if ($canQuickRedact)
        <section class="moderation-panel__section moderation-panel__section--quick" aria-labelledby="{{ $idPrefix }}-quick-redact-title">
            <div class="moderation-panel__section-head">
                <h4 id="{{ $idPrefix }}-quick-redact-title" class="moderation-panel__section-title">{{ __('staff.moderation.quick_title') }}</h4>
                <p class="moderation-panel__hint">{{ __('staff.moderation.quick_lede') }}</p>
            </div>

            <form data-loading-submit method="POST" action="{{ $quickRedactAction }}" class="moderation-panel__quick-form">
                @csrf
                <div class="moderation-field">
                    @if ($complaint->namedParty())
                        <label class="moderation-check" for="{{ $idPrefix }}-quick-redact-party">
                            <input type="hidden" name="strip_party" value="0">
                            <input type="checkbox" id="{{ $idPrefix }}-quick-redact-party" name="strip_party" value="1">
                            <span class="moderation-check__text">{{ __('staff.moderation.quick_party') }} — <strong>{{ $complaint->namedParty() }}</strong></span>
                        </label>
                    @endif

                    @if ($complaint->namedPartyPosition())
                        <label class="moderation-check" for="{{ $idPrefix }}-quick-redact-position">
                            <input type="hidden" name="strip_position" value="0">
                            <input type="checkbox" id="{{ $idPrefix }}-quick-redact-position" name="strip_position" value="1">
                            <span class="moderation-check__text">{{ __('staff.moderation.quick_position') }} — <strong>{{ $complaint->namedPartyPosition() }}</strong></span>
                        </label>
                    @endif
                </div>

                <button type="submit" class="moderation-panel__submit moderation-panel__submit--secondary">{{ __('staff.moderation.quick_submit') }}</button>
            </form>
        </section>
    @endif

    <form data-loading-submit method="POST" action="{{ $action }}" class="moderation-panel__form">
        @csrf

        <section class="moderation-panel__section" aria-labelledby="{{ $idPrefix }}-visibility-title">
            <div class="moderation-panel__section-head">
                <h4 id="{{ $idPrefix }}-visibility-title" class="moderation-panel__section-title">{{ __('staff.moderation.hide') }}</h4>
            </div>
            <div class="moderation-field">
                <label class="moderation-check" for="{{ $idPrefix }}-moderation-hide">
                    <input type="hidden" name="hidden_from_public" value="0">
                    <input type="checkbox" id="{{ $idPrefix }}-moderation-hide" name="hidden_from_public" value="1" @checked($complaint->hidden_from_public)>
                    <span class="moderation-check__text">{{ __('staff.moderation.hide') }}</span>
                </label>
            </div>
        </section>

        <section class="moderation-panel__section" aria-labelledby="{{ $idPrefix }}-redaction-title">
            <div class="moderation-panel__section-head">
                <h4 id="{{ $idPrefix }}-redaction-title" class="moderation-panel__section-title">{{ __('staff.moderation.redacted_title') }} &amp; {{ __('staff.moderation.redacted_description') }}</h4>
                <p class="moderation-panel__hint">{{ $complaint->isRedacted() ? __('staff.moderation.redacted_state_ready', ['default' => 'A redacted public copy is saved. You can adjust it below.']) : __('staff.moderation.redacted_state_empty', ['default' => 'Leave blank to keep the original public text.']) }}</p>
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
        </section>

        @if (count($allImages) > 0)
            <section class="moderation-panel__section" aria-labelledby="{{ $idPrefix }}-photos-title">
                <div class="moderation-panel__section-head">
                    <h4 id="{{ $idPrefix }}-photos-title" class="moderation-panel__section-title">{{ __('staff.moderation.photos') }}</h4>
                </div>
                <div class="moderation-field">
                    <div class="moderation-photos">
                        @foreach ($allImages as $path)
                            @php $isWithheld = in_array($path, $complaint->withheldImagePaths(), true); @endphp
                            <div class="moderation-photo {{ $isWithheld ? 'moderation-photo--withheld' : '' }}">
                                <span class="moderation-photo__name" title="{{ basename($path) }}">{{ basename($path) }}</span>
                                @if ($isWithheld)
                                    <label class="moderation-photo__action moderation-photo__action--restore">
                                        <input type="checkbox" name="restore_image_paths[]" value="{{ $path }}"> {{ __('staff.moderation.restore') }}
                                    </label>
                                @else
                                    <label class="moderation-photo__action">
                                        <input type="checkbox" name="withheld_image_paths[]" value="{{ $path }}"> {{ __('staff.moderation.withhold') }}
                                    </label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="moderation-panel__section moderation-panel__section--reason" aria-labelledby="{{ $idPrefix }}-reason-title">
            <div class="moderation-panel__section-head">
                <h4 id="{{ $idPrefix }}-reason-title" class="moderation-panel__section-title">{{ __('staff.moderation.reason') }} <span class="moderation-required" aria-hidden="true">*</span></h4>
            </div>
            <div class="moderation-field moderation-field--required">
                <label for="{{ $idPrefix }}-moderation_reason" class="sr-only">{{ __('staff.moderation.reason') }}</label>
                <textarea id="{{ $idPrefix }}-moderation_reason" name="moderation_reason" rows="3" maxlength="1000" required aria-required="true"
                          placeholder="{{ __('staff.moderation.reason_placeholder') }}">{{ old('moderation_reason', $complaint->moderation_reason) }}</textarea>
                @error('moderation_reason') <div class="filing-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="moderation-panel__submit">{{ __('staff.moderation.save') }}</button>
        </section>
    </form>
</div>
