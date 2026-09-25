@extends('layouts.guest')

@section('title', 'Accept Staff Invitation — Daet Listens')
@section('content')
<div class="invitation-page">
    <section class="invitation-panel" aria-labelledby="invitation-heading">
        <div class="invitation-panel-heading">
            <span class="invitation-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 7l8 4 8-4-8-4Z"></path><path d="M4 12h16"></path><path d="M4 17h16"></path><path d="m4 7 8 4 8-4"></path></svg>
            </span>
            <div>
                <p class="invitation-overline">Official staff onboarding</p>
                <h1 id="invitation-heading">Create your staff account</h1>
                <p>Your invitation is assigned to <strong>{{ $invitation->department->name }}</strong>. Set a password to activate access.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="invitation-errors" role="alert">
                <p>Please correct the following:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitations.redeem', $invitation->code) }}" class="invitation-form">
            @csrf
            <div class="invitation-form-grid">
                <label class="invitation-field">
                    <span>First name</span>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" maxlength="100">
                </label>
                <label class="invitation-field">
                    <span>Last name</span>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" maxlength="100">
                </label>
            </div>
            <label class="invitation-field">
                <span>Email address</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255">
            </label>
            <label class="invitation-field">
                <span>Contact number <small>Optional</small></span>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" inputmode="numeric" autocomplete="tel" placeholder="09171234567">
            </label>
            <div class="invitation-form-grid">
                <label class="invitation-field">
                    <span>Password</span>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                </label>
                <label class="invitation-field">
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
            </div>
            @include('auth.partials.password-requirements', ['passwordTarget' => 'password'])
            <label class="invitation-terms">
                <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                <span>I agree to use this account only for official municipal service responsibilities.</span>
            </label>
            <button type="submit" class="invitation-submit">Activate staff account</button>
        </form>
    </section>
</div>
@endsection
