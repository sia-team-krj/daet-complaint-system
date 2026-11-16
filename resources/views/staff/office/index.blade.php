@extends('staff.layouts.app')

@section('title', __('office.title') . ' - Daet Listens')
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <div class="office-banner">
                <div>
                    <h1 class="tr-h1">{{ __('office.title') }}</h1>
                    <p class="tr-privacy">{{ __('office.lede', ['office' => $office->name]) }}</p>
                </div>
                <a href="{{ route('staff.dashboard') }}" class="staff-button staff-button--secondary">{{ __('office.back_to_dashboard') }}</a>
            </div>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <section class="dept-stats" aria-label="{{ __('office.title') }}" style="margin-top: -16px; margin-bottom: 24px;">
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--navy dept-si dept-si--navy" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['members'] }}</span>
                    <span class="dept-stat__label">{{ __('office.metrics.members') }}</span>
                </span>
            </div>
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--green dept-si dept-si--green" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['active'] }}</span>
                    <span class="dept-stat__label">{{ __('office.metrics.active') }}</span>
                </span>
            </div>
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--amber dept-si dept-si--amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['verifiers'] }}</span>
                    <span class="dept-stat__label">{{ __('office.metrics.verifiers') }}</span>
                </span>
            </div>
        </section>

        @include('staff.partials.flash')

        <div class="office-grid">
            <div class="office-stack">
                <section class="tr-card office-panel" aria-labelledby="office-members-title">
                    <div class="office-panel__header">
                        <div>
                            <h2 class="tr-h2 office-panel__title" id="office-members-title">{{ __('office.members.title') }}</h2>
                            <p class="office-panel__description">{{ __('office.members.description') }}</p>
                        </div>
                    </div>

                    @if($members->isEmpty())
                        <p class="office-empty">{{ __('office.members.empty') }}</p>
                    @else
                        <ul class="office-member-list">
                            @foreach($members as $member)
                                @php
                                    $isSelf = $member->is(auth()->user());
                                    $isManager = $member->isDepartmentAdmin();
                                @endphp
                                <li class="office-member">
                                    <div class="office-member__identity">
                                        <span class="office-member__avatar" aria-hidden="true">{{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}</span>
                                        <div>
                                            <p class="office-member__name">
                                                {{ $member->full_name }}
                                                @if($isSelf)
                                                    <span class="office-badge">{{ __('office.members.you') }}</span>
                                                @endif
                                            </p>
                                            <p class="office-member__meta">{{ $member->email }}</p>
                                            <div class="office-member__badges">
                                                <span class="office-badge office-badge--role">{{ __('roles.' . $member->role) }}</span>
                                                <span class="office-badge {{ $member->is_active ? 'office-badge--active' : 'office-badge--inactive' }}">{{ $member->is_active ? __('office.members.active') : __('office.members.inactive') }}</span>
                                                @if($member->canVerifyComplaints())
                                                    <span class="office-badge office-badge--verifier">{{ __('office.members.verifier') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if(! $isManager)
                                        <div class="office-member__actions">
                                            <form method="POST" action="{{ route('staff.office.staff.toggle', $member) }}">
                                                @csrf
                                                <button type="submit" class="staff-button staff-button--secondary staff-button--compact">
                                                    {{ $member->is_active ? __('office.members.deactivate') : __('office.members.activate') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('staff.office.staff.toggle-verification', $member) }}">
                                                @csrf
                                                <button type="submit" class="staff-button staff-button--secondary staff-button--compact">
                                                    {{ $member->can_verify ? __('office.members.revoke_verification') : __('office.members.grant_verification') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="office-help">{{ __('office.members.peer_note') }}</p>
                    @endif
                </section>

                <section class="tr-card office-panel" aria-labelledby="office-add-title">
                    <div class="office-panel__header">
                        <div>
                            <h2 class="tr-h2 office-panel__title" id="office-add-title">{{ __('office.add.title') }}</h2>
                            <p class="office-panel__description">{{ __('office.add.description') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('staff.office.staff.store') }}" class="office-form">
                        @csrf
                        <div class="office-form__row">
                            <label class="office-field">
                                <span>{{ __('office.add.first_name') }}</span>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" class="office-input" required autocomplete="given-name">
                                @error('first_name')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="office-field">
                                <span>{{ __('office.add.last_name') }}</span>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" class="office-input" required autocomplete="family-name">
                                @error('last_name')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <div class="office-form__row">
                            <label class="office-field">
                                <span>{{ __('office.add.email') }}</span>
                                <input type="email" name="email" value="{{ old('email') }}" class="office-input" required autocomplete="email">
                                @error('email')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="office-field">
                                <span>{{ __('office.add.contact') }} <small>{{ __('office.add.contact_optional') }}</small></span>
                                <input type="tel" name="contact_number" value="{{ old('contact_number') }}" class="office-input" inputmode="numeric" autocomplete="tel">
                                @error('contact_number')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <div class="office-form__row">
                            <label class="office-field">
                                <span>{{ __('office.add.method') }}</span>
                                <select name="creation_method" class="office-input">
                                    <option value="emailed_temp_password" @selected(old('creation_method', 'emailed_temp_password') === 'emailed_temp_password')>{{ __('office.add.temp_password') }}</option>
                                    <option value="invitation" @selected(old('creation_method') === 'invitation')>{{ __('office.add.invitation') }}</option>
                                </select>
                                @error('creation_method')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="office-field">
                                <span>{{ __('office.add.expiry') }}</span>
                                <select name="invitation_expires_in_hours" class="office-input">
                                    <option value="24" @selected(old('invitation_expires_in_hours') === '24')>{{ __('office.add.expiry_24') }}</option>
                                    <option value="48" @selected(old('invitation_expires_in_hours') === '48')>{{ __('office.add.expiry_48') }}</option>
                                    <option value="168" @selected(old('invitation_expires_in_hours') === '168')>{{ __('office.add.expiry_168') }}</option>
                                </select>
                                @error('invitation_expires_in_hours')<span class="office-error">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <p class="office-help">{{ __('office.add.note', ['office' => $office->name]) }}</p>

                        <div class="office-form__actions">
                            <button type="submit" class="staff-button staff-button--primary">{{ __('office.add.submit') }}</button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="office-stack">
                <section class="tr-card office-panel" aria-labelledby="office-invitations-title">
                    <div class="office-panel__header">
                        <div>
                            <h2 class="tr-h2 office-panel__title" id="office-invitations-title">{{ __('office.invitations.title') }}</h2>
                            <p class="office-panel__description">{{ __('office.invitations.description') }}</p>
                        </div>
                    </div>

                    @if($invitations->isEmpty())
                        <p class="office-empty">{{ __('office.invitations.empty') }}</p>
                    @else
                        <ul class="office-invitation-list">
                            @foreach($invitations as $invitation)
                                <li class="office-invitation">
                                    <div>
                                        <p class="office-member__name">{{ $invitation->invitee_name ?: $invitation->invitee_email }}</p>
                                        <p class="office-member__meta">{{ $invitation->invitee_email }}</p>
                                        <p class="office-member__meta">{{ __('office.invitations.expires', ['date' => $invitation->expires_at?->format('M j, Y g:i A')]) }}</p>
                                    </div>
                                    <label class="office-field">
                                        <span>{{ __('office.invitations.copy') }}</span>
                                        <input type="text" class="office-input office-input--link" readonly value="{{ route('invitations.show', $invitation->code) }}" onclick="this.select()">
                                    </label>
                                    <form method="POST" action="{{ route('staff.office.invitations.revoke', $invitation) }}" onsubmit="return confirm('{{ __('office.invitations.revoke_confirm') }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="staff-button staff-button--secondary staff-button--compact">{{ __('office.invitations.revoke') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="tr-card office-panel" aria-labelledby="office-audit-title">
                    <div class="office-panel__header">
                        <div>
                            <h2 class="tr-h2 office-panel__title" id="office-audit-title">{{ __('office.audit.title') }}</h2>
                            <p class="office-panel__description">{{ __('office.audit.description') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('staff.office.audit.index') }}" class="staff-button staff-button--secondary">{{ __('office.audit.open') }}</a>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
