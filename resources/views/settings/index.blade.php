@extends('layouts.app')
@section('title', __('settings.title').' - Daet Listens')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@section('content')
{{--
    Settings.

    One section at a time, switched by the tabs — the way it always worked.
    Each section is a card in the portal's own language, with the fields
    grouped inside it and the save row living with the fields it saves.

    The save row says what happened quietly, in place. There is no sticky bar
    over the page and no browser "leave site?" prompt: a resident who typed
    something and wandered off should not be chased by the browser for it.
--}}
<div class="settings-page">
    <div class="tr-container settings-container">

        <header class="settings-header">
            <h1 class="tr-h1 settings-header__title">{{ __('settings.title') }}</h1>
            <p class="tr-privacy settings-header__lede">{{ __('settings.subtitle') }}</p>
        </header>

        <div class="settings-body">
            <nav class="settings-tabs" aria-label="{{ __('settings.title') }}">
                <ul role="tablist">
                    @foreach(['profile', 'security', 'notifications', 'preferences'] as $t)
                        <li role="presentation">
                            <a href="{{ route('settings', ['tab' => $t]) }}" role="tab"
                               aria-selected="{{ $tab === $t ? 'true' : 'false' }}"
                               class="settings-tab {{ $tab === $t ? 'is-active' : '' }}"
                               @if($tab === $t) aria-current="page" @endif
                               data-settings-tab="{{ $t }}">{{ __('settings.tabs.'.$t) }}</a>
                        </li>
                    @endforeach
                    @if($user->role === 'citizen')
                        <li role="presentation">
                            <a href="{{ route('settings', ['tab' => 'privacy']) }}" role="tab"
                               aria-selected="{{ $tab === 'privacy' ? 'true' : 'false' }}"
                               class="settings-tab {{ $tab === 'privacy' ? 'is-active' : '' }}"
                               @if($tab === 'privacy') aria-current="page" @endif
                               data-settings-tab="privacy">{{ __('settings.privacy.title') }}</a>
                        </li>
                    @endif
                </ul>
            </nav>

            <div class="settings-sections">
                @if($errors->any())
                    <div class="settings-alert settings-alert--danger" role="alert" aria-live="assertive">
                        <ul>
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{-- ── Profile ──────────────────────────────────────────── --}}
                @if($tab === 'profile')
                    <section class="tr-card settings-card" id="tab-profile" role="tabpanel" aria-labelledby="tab-profile">
                        <header class="settings-card__head">
                            <h2 class="settings-card__title">{{ __('settings.profile.title') }}</h2>
                            <p class="settings-card__lede">{{ __('settings.profile.lede') }}</p>
                        </header>

                        <form data-loading-submit method="POST" action="{{ route('settings.profile.update') }}" data-settings-form>
                            @csrf

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.profile.group_details') }}</legend>
                                <p class="settings-group__help">{{ __('settings.profile.group_details_help') }}</p>

                                <div class="settings-grid">
                                    <div class="settings-field">
                                        <label class="settings-field__label" for="settings-first-name">{{ __('settings.profile.first_name') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                        <input id="settings-first-name" type="text" name="first_name" class="settings-input" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name" data-name-field="first_name" aria-describedby="settings-first-name-hint">
                                        <p class="settings-field__hint" id="settings-first-name-hint">{{ __('settings.profile.name_hint') }}</p>
                                        <p class="settings-field__error" data-name-error="first_name" hidden></p>
                                    </div>

                                    <div class="settings-field">
                                        <label class="settings-field__label" for="settings-last-name">{{ __('settings.profile.last_name') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                        <input id="settings-last-name" type="text" name="last_name" class="settings-input" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name" data-name-field="last_name" aria-describedby="settings-last-name-hint">
                                        <p class="settings-field__hint" id="settings-last-name-hint">{{ __('settings.profile.name_hint') }}</p>
                                        <p class="settings-field__error" data-name-error="last_name" hidden></p>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.profile.group_contact') }}</legend>
                                <p class="settings-group__help">{{ __('settings.profile.group_contact_help') }}</p>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="settings-email">{{ __('settings.profile.email') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                    <input id="settings-email" type="email" name="email" class="settings-input" value="{{ old('email', $user->email) }}" required autocomplete="email">
                                </div>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="settings-contact">{{ __('settings.profile.contact_number') }}</label>
                                    <input id="settings-contact" type="text" name="contact_number" class="settings-input" value="{{ old('contact_number', $user->contact_number) }}" inputmode="numeric" placeholder="09171234567">
                                    <p class="settings-field__hint">{{ __('settings.profile.contact_hint') }}</p>
                                </div>
                            </fieldset>

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.profile.group_place') }}</legend>
                                <p class="settings-group__help">{{ __('settings.profile.group_place_help') }}</p>

                                @if($user->role === 'citizen')
                                    <div class="settings-field">
                                        <label class="settings-field__label" for="settings-barangay">{{ __('settings.profile.barangay') }}</label>
                                        <select id="settings-barangay" name="barangay" class="settings-input">
                                            <option value="">—</option>
                                            @foreach(\App\Models\User::BARANGAYS as $b)
                                                <option value="{{ $b }}" @selected(old('barangay', $user->barangay) === $b)>{{ $b }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if($user->isDepartmentStaff())
                                    <div class="settings-field">
                                        <span class="settings-field__label">{{ __('settings.profile.department') }}</span>
                                        <input type="text" class="settings-input" value="{{ $user->department?->name ?? '—' }}" disabled>
                                        <p class="settings-field__hint">{{ __('settings.profile.department_readonly') }}</p>
                                    </div>
                                @endif
                            </fieldset>

                            <div class="settings-save-row">
                                @include('settings.partials.save-state', ['section' => 'profile'])
                                <div class="settings-save-row__actions">
                                    <button type="button" class="tr-button tr-button--secondary" data-discard hidden>{{ __('settings.profile.discard') }}</button>
                                    <button type="submit" class="tr-button tr-button--primary" data-save-button>{{ __('settings.profile.save') }}</button>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif

                {{-- ── Security ─────────────────────────────────────────── --}}
                @if($tab === 'security')
                    <section class="tr-card settings-card" id="tab-security" role="tabpanel" aria-labelledby="tab-security">
                        <header class="settings-card__head">
                            <h2 class="settings-card__title">{{ __('settings.security.title') }}</h2>
                            <p class="settings-card__lede">{{ __('settings.security.lede') }}</p>
                        </header>

                        <form data-loading-submit method="POST" action="{{ route('settings.password.update') }}" data-settings-form>
                            @csrf

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.security.change_password') }}</legend>
                                <p class="settings-group__help">{{ __('settings.security.change_password_help') }}</p>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="settings-current">{{ __('settings.security.current_password') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                    <span class="settings-password-wrap">
                                        <input id="settings-current" type="password" name="current_password" class="settings-input" required autocomplete="current-password" @error('current_password') aria-invalid="true" @enderror>
                                        <button type="button" class="settings-password-toggle" data-password-toggle="settings-current" aria-label="{{ __('settings.security.show_password') }}" aria-pressed="false">{{ __('settings.security.show') }}</button>
                                    </span>
                                    @error('current_password')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="settings-new-password">{{ __('settings.security.new_password') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                    <span class="settings-password-wrap">
                                        <input id="settings-new-password" type="password" name="new_password" class="settings-input" required autocomplete="new-password" @error('new_password') aria-invalid="true" @enderror>
                                        <button type="button" class="settings-password-toggle" data-password-toggle="settings-new-password" aria-label="{{ __('settings.security.show_password') }}" aria-pressed="false">{{ __('settings.security.show') }}</button>
                                    </span>
                                    @error('new_password')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                                    @include('auth.partials.password-requirements', ['passwordTarget' => 'settings-new-password'])
                                </div>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="settings-confirm">{{ __('settings.security.confirm_password') }} <span class="required-mark" aria-hidden="true">*</span></label>
                                    <span class="settings-password-wrap">
                                        <input id="settings-confirm" type="password" name="new_password_confirmation" class="settings-input" required autocomplete="new-password">
                                        <button type="button" class="settings-password-toggle" data-password-toggle="settings-confirm" aria-label="{{ __('settings.security.show_password') }}" aria-pressed="false">{{ __('settings.security.show') }}</button>
                                    </span>
                                </div>
                            </fieldset>

                            <div class="settings-save-row">
                                @include('settings.partials.save-state', ['section' => 'security'])
                                <div class="settings-save-row__actions">
                                    <button type="submit" class="tr-button tr-button--primary" data-save-button>{{ __('settings.security.update_password') }}</button>
                                </div>
                            </div>
                        </form>

                        <fieldset class="settings-group">
                            <legend class="settings-group__legend">{{ __('settings.security.sessions') }}</legend>
                            <p class="settings-group__help">{{ __('settings.security.sessions_help') }}</p>

                            @if($sessions->isEmpty())
                                <p class="settings-empty">{{ __('settings.security.sessions_empty') }}</p>
                            @else
                                <ul class="settings-sessions" id="settings-sessions">
                                    @foreach($sessions as $index => $s)
                                        <li class="settings-session" @if($index >= 5) data-session-extra hidden @endif>
                                            <span class="settings-session__body">
                                                <strong>{{ $s->friendly }}</strong>
                                                @if($s->is_current)<span class="settings-session__badge">{{ __('settings.security.this_device') }}</span>@endif
                                                <span class="settings-session__meta">{{ $s->ip }} · <time datetime="{{ $s->last_activity->toIso8601String() }}" title="{{ $s->last_full }}" aria-label="{{ $s->last_full }}">{{ $s->last_relative }}</time></span>
                                            </span>
                                            @if(!$s->is_current)
                                                <form data-loading-submit method="POST" action="{{ route('settings.sessions.logout', $s->id) }}">@csrf<button type="submit" class="tr-button tr-button--secondary tr-button--compact">{{ __('settings.security.logout_session') }}</button></form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if($sessions->count() > 5)
                                    <button type="button" class="tr-button tr-button--secondary" id="settings-show-more">{{ __('settings.security.show_more_sessions') }}</button>
                                @endif
                            @endif
                        </fieldset>

                        <div class="settings-save-row">
                            <form data-loading-submit method="POST" action="{{ route('settings.logout-other-devices') }}" id="logout-other-form">
                                @csrf
                                <button type="button" class="tr-button tr-button--danger" id="logout-other-btn">{{ __('settings.security.logout_other_devices') }}</button>
                            </form>
                        </div>

                        <dialog id="logout-other-dialog" class="settings-dialog" aria-labelledby="logout-other-title">
                            <h3 id="logout-other-title">{{ __('settings.security.logout_other_devices') }}</h3>
                            <p>{{ __('settings.security.logout_other_done') }}</p>
                            <div class="settings-dialog__actions">
                                <form data-loading-submit method="dialog"><button class="tr-button tr-button--secondary" formmethod="dialog">{{ __('settings.security.cancel') }}</button></form>
                                <form data-loading-submit method="POST" action="{{ route('settings.logout-other-devices') }}">@csrf<button type="submit" class="tr-button tr-button--danger">{{ __('settings.security.confirm') }}</button></form>
                            </div>
                        </dialog>
                    </section>
                @endif

                {{-- ── Notifications ─────────────────────────────────────── --}}
                @if($tab === 'notifications')
                    <section class="tr-card settings-card" id="tab-notifications" role="tabpanel" aria-labelledby="tab-notifications">
                        <header class="settings-card__head">
                            <h2 class="settings-card__title">{{ __('settings.notifications.title') }}</h2>
                            <p class="settings-card__lede">{{ __('settings.notifications.lede') }}</p>
                        </header>

                        <form data-loading-submit method="POST" action="{{ route('settings.notifications.update') }}" data-settings-form>
                            @csrf
                            @php $prefs = $user->notification_preferences ?? ['channels'=>['in_app','email'],'events'=>$notificationEvents]; @endphp

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.notifications.channels') }}</legend>
                                <div class="settings-toggle-row">
                                    <span class="settings-toggle-row__body">
                                        <span class="settings-toggle-row__label">{{ __('settings.notifications.in_app') }}</span>
                                        <span class="settings-toggle-row__desc">{{ __('settings.notifications_desc.in_app') }}</span>
                                    </span>
                                    <label class="toggle-switch"><input type="checkbox" name="channels[]" value="in_app" @checked(in_array('in_app', $prefs['channels'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.in_app') }}</span></label>
                                </div>
                                <div class="settings-toggle-row">
                                    <span class="settings-toggle-row__body">
                                        <span class="settings-toggle-row__label">{{ __('settings.notifications.email') }}</span>
                                        <span class="settings-toggle-row__desc">{{ __('settings.notifications_desc.email') }}</span>
                                    </span>
                                    <label class="toggle-switch"><input type="checkbox" name="channels[]" value="email" @checked(in_array('email', $prefs['channels'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.email') }}</span></label>
                                </div>
                            </fieldset>

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.notifications.events') }}</legend>
                                @foreach($notificationEvents as $event)
                                    <div class="settings-toggle-row">
                                        <span class="settings-toggle-row__body">
                                            <span class="settings-toggle-row__label">{{ __('settings.notifications.'.$event) }}</span>
                                            <span class="settings-toggle-row__desc">{{ __('settings.notifications_desc.'.$event) }}</span>
                                        </span>
                                        <label class="toggle-switch"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, $prefs['events'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.'.$event) }}</span></label>
                                    </div>
                                @endforeach
                            </fieldset>

                            @if($user->isDepartmentStaff())
                                <fieldset class="settings-group">
                                    <legend class="settings-group__legend">{{ __('settings.notifications.default_queue_view') }}</legend>
                                    <div class="settings-field">
                                        <label class="settings-field__label" for="settings-queue">{{ __('settings.notifications.default_queue_view') }}</label>
                                        <select id="settings-queue" name="default_queue_view" class="settings-input">
                                            <option value="all" @selected($user->default_queue_view === 'all')>{{ __('settings.notifications.queue_all') }}</option>
                                            <option value="mine" @selected($user->default_queue_view === 'mine')>{{ __('settings.notifications.queue_mine') }}</option>
                                            <option value="unassigned" @selected($user->default_queue_view === 'unassigned')>{{ __('settings.notifications.queue_unassigned') }}</option>
                                        </select>
                                    </div>
                                    <div class="settings-toggle-row">
                                        <span class="settings-toggle-row__body">
                                            <span class="settings-toggle-row__label">{{ __('settings.notifications.daily_digest') }}</span>
                                            <span class="settings-toggle-row__desc">{{ __('settings.notifications.daily_digest_hint') }}</span>
                                        </span>
                                        <label class="toggle-switch"><input type="checkbox" name="daily_digest" value="1" @checked($user->daily_digest)><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.daily_digest') }}</span></label>
                                    </div>
                                </fieldset>
                            @endif

                            <div class="settings-save-row">
                                @include('settings.partials.save-state', ['section' => 'notifications'])
                                <div class="settings-save-row__actions">
                                    <button type="submit" class="tr-button tr-button--primary" data-save-button>{{ __('settings.notifications.save') }}</button>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif

                {{-- ── Preferences ───────────────────────────────────────── --}}
                @if($tab === 'preferences')
                    <section class="tr-card settings-card" id="tab-preferences" role="tabpanel" aria-labelledby="tab-preferences">
                        <header class="settings-card__head">
                            <h2 class="settings-card__title">{{ __('settings.preferences.title') }}</h2>
                            <p class="settings-card__lede">{{ __('settings.preferences.lede') }}</p>
                        </header>

                        {{-- These save the moment they change, so there is no save row. --}}
                        <form method="POST" action="{{ route('settings.preferences.update') }}" id="preferences-form" data-preferences-autosave>
                            @csrf

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.preferences.language') }}</legend>
                                <p class="settings-group__help">{{ __('settings.preferences.language_hint') }}</p>
                                <div class="segmented-control" role="radiogroup" aria-label="{{ __('settings.preferences.language') }}">
                                    @foreach(config('localization.supported') as $code => $label)
                                        <label class="segmented-control__option"><input type="radio" name="locale" value="{{ $code }}" data-preference-field @checked(app()->getLocale() === $code || $user->locale === $code)> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <fieldset class="settings-group">
                                <legend class="settings-group__legend">{{ __('settings.preferences.text_size') }}</legend>
                                <div class="segmented-control" role="radiogroup" aria-label="{{ __('settings.preferences.text_size') }}">
                                    <label class="segmented-control__option"><input type="radio" name="text_size" value="normal" data-preference-field @checked($user->text_size !== 'large')><span class="segmented-control__preview">Aa</span> {{ __('settings.preferences.text_normal') }}</label>
                                    <label class="segmented-control__option"><input type="radio" name="text_size" value="large" data-preference-field @checked($user->text_size === 'large')><span class="segmented-control__preview" style="font-size:20px">Aa</span> {{ __('settings.preferences.text_large') }}</label>
                                </div>
                            </fieldset>

                            @include('settings.partials.save-state', ['section' => 'preferences'])
                        </form>
                    </section>
                @endif

                {{-- ── Privacy ───────────────────────────────────────────── --}}
                @if($tab === 'privacy' && $user->role === 'citizen')
                    <section class="tr-card settings-card" id="tab-privacy" role="tabpanel" aria-labelledby="tab-privacy">
                        <header class="settings-card__head">
                            <h2 class="settings-card__title">{{ __('settings.privacy.title') }}</h2>
                            <p class="settings-card__lede">{{ __('settings.privacy.lede') }}</p>
                        </header>

                        <fieldset class="settings-group">
                            <legend class="settings-group__legend">{{ __('settings.privacy.download') }}</legend>
                            <p class="settings-group__help">{{ __('settings.privacy.download_hint') }}</p>
                            <a href="{{ route('settings.download-data') }}" class="tr-button tr-button--secondary">{{ __('settings.privacy.download_button') }}</a>
                        </fieldset>

                        <form data-loading-submit method="POST" action="{{ route('settings.delete-account') }}" data-settings-form onsubmit="return confirm('{{ __('settings.privacy.delete_hint') }}')">
                            @csrf
                            <fieldset class="settings-group settings-group--danger">
                                <legend class="settings-group__legend">{{ __('settings.privacy.delete') }}</legend>
                                <p class="settings-group__help">{{ __('settings.privacy.delete_hint') }}</p>
                                <p class="settings-group__help">{{ __('settings.privacy.delete_confirm') }}</p>

                                <div class="settings-field">
                                    <label class="settings-field__label" for="delete-email">{{ $user->email }}</label>
                                    <input id="delete-email" type="text" name="delete_email" class="settings-input" required autocomplete="off">
                                </div>
                                <div class="settings-field">
                                    <label class="settings-field__label" for="delete-password">{{ __('settings.security.current_password') }}</label>
                                    <input id="delete-password" type="password" name="delete_password" class="settings-input" required autocomplete="current-password">
                                </div>
                            </fieldset>

                            <div class="settings-save-row">
                                @include('settings.partials.save-state', ['section' => 'privacy'])
                                <div class="settings-save-row__actions">
                                    <button type="submit" class="tr-button tr-button--danger">{{ __('settings.privacy.delete_button') }}</button>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
