@extends('admin.layouts.app')

@section('title', 'Create Staff Account - Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow staff-create-page">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.staff.index') }}">Staff accounts</a>
        <span aria-hidden="true">/</span>
        <span>Create account</span>
    </nav>

    <header class="admin-page-header staff-create-header">
        <div>
            <h1 class="admin-page-title">Create staff account</h1>
            <p class="admin-page-description">Create a staff account directly, or send an expiring invitation so the staff member sets their own password.</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">Staff details</h2>
        </div>
        <form data-loading-submit method="POST" action="{{ route('admin.staff.store') }}" class="admin-panel-body admin-form-stack staff-create-form" id="staff-create-form" novalidate>
            @csrf
            <p class="staff-required-note"><span class="required-mark" aria-hidden="true">*</span> Required field</p>
            <div class="admin-form-grid">
                <label class="admin-field">
                    <span>First name <span class="required-mark" aria-hidden="true">*</span></span>
                    <input type="text" name="first_name" class="admin-input" value="{{ old('first_name') }}" required maxlength="100" autocomplete="off" @error('first_name') aria-invalid="true" aria-describedby="error-first_name" @enderror>
                    @error('first_name')<span class="admin-field-error" id="error-first_name"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
                </label>
                <label class="admin-field">
                    <span>Last name <span class="required-mark" aria-hidden="true">*</span></span>
                    <input type="text" name="last_name" class="admin-input" value="{{ old('last_name') }}" required maxlength="100" autocomplete="off" @error('last_name') aria-invalid="true" aria-describedby="error-last_name" @enderror>
                    @error('last_name')<span class="admin-field-error" id="error-last_name"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
                </label>
            </div>
            <label class="admin-field">
                <span>Email address <span class="required-mark" aria-hidden="true">*</span></span>
                <input type="email" name="email" class="admin-input" value="{{ old('email') }}" required maxlength="255" autocomplete="off" @error('email') aria-invalid="true" aria-describedby="error-email" @enderror>
                @error('email')<span class="admin-field-error" id="error-email"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
            </label>
            <label class="admin-field">
                <span>Contact number <span class="optional-mark">(Optional)</span></span>
                <input type="text" name="contact_number" class="admin-input" value="{{ old('contact_number') }}" inputmode="numeric" placeholder="09171234567" autocomplete="off" @error('contact_number') aria-invalid="true" aria-describedby="error-contact_number" @enderror>
                @error('contact_number')<span class="admin-field-error" id="error-contact_number"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
            </label>
            <label class="admin-field">
                <span>Department <span class="required-mark" aria-hidden="true">*</span></span>
                <select name="department_id" class="admin-input" required @error('department_id') aria-invalid="true" aria-describedby="error-department_id" @enderror>
                    <option value="">Select a department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<span class="admin-field-error" id="error-department_id"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
            </label>

            <fieldset class="staff-method-fieldset">
                <legend>How should this account get its password? <span class="required-mark" aria-hidden="true">*</span></legend>

                <label class="staff-method-card" data-method="invitation">
                    <input type="radio" name="creation_method" value="invitation" @checked(old('creation_method') === 'invitation')>
                    <span class="staff-method-card__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></span>
                    <span class="staff-method-card__body">
                        <span class="staff-method-card__title">Send invitation link</span>
                        <span class="staff-method-card__helper">The staff member receives an expiring link and sets their own password. Most secure and fully traceable.</span>
                    </span>
                </label>

                <label class="staff-method-card" data-method="emailed_temp_password">
                    <input type="radio" name="creation_method" value="emailed_temp_password" @checked(old('creation_method', 'emailed_temp_password') === 'emailed_temp_password')>
                    <span class="staff-method-card__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></span>
                    <span class="staff-method-card__body">
                        <span class="staff-method-card__title">Email a temporary password</span>
                        <span class="staff-method-card__helper">A temporary password is generated and emailed to the staff member.</span>
                    </span>
                </label>

                <label class="staff-method-card" data-method="manual_password">
                    <input type="radio" name="creation_method" value="manual_password" @checked(old('creation_method') === 'manual_password')>
                    <span class="staff-method-card__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></span>
                    <span class="staff-method-card__body">
                        <span class="staff-method-card__title">Set a password manually (no email sent)</span>
                        <span class="staff-method-card__helper">You choose a temporary password and give it to the staff member yourself. No email is sent.</span>
                    </span>
                </label>
                @error('creation_method')<span class="admin-field-error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror

                <div class="staff-method-panel" data-panel="invitation" hidden>
                    <label class="admin-field">
                        <span>Invitation expires in <span class="required-mark" aria-hidden="true">*</span></span>
                        <select name="invitation_expires_in_hours" class="admin-input" data-method-field="invitation">
                            <option value="24" @selected((string) old('invitation_expires_in_hours', '48') === '24')>24 hours</option>
                            <option value="48" @selected((string) old('invitation_expires_in_hours', '48') === '48')>48 hours</option>
                            <option value="168" @selected((string) old('invitation_expires_in_hours', '48') === '168')>7 days</option>
                        </select>
                        @error('invitation_expires_in_hours')<span class="admin-field-error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
                    </label>
                </div>

                <div class="staff-method-panel" data-panel="manual_password" hidden>
                    <div class="admin-field">
                        <label for="temporary_password"><span>Temporary password <span class="required-mark" aria-hidden="true">*</span></span></label>
                        <div class="staff-password-row">
                            <input id="temporary_password" type="password" name="temporary_password" class="admin-input" value="{{ old('temporary_password') }}" autocomplete="new-password" data-method-field="manual_password" @error('temporary_password') aria-invalid="true" aria-describedby="error-temporary_password" @enderror>
                            <button type="button" class="admin-button admin-button--secondary staff-password-toggle" data-password-toggle="temporary_password" aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <span>Show</span>
                            </button>
                            <button type="button" class="admin-button admin-button--secondary" id="generate-password-btn">Generate strong password</button>
                            <button type="button" class="admin-button admin-button--secondary" id="copy-password-btn">Copy</button>
                        </div>
                        <span class="admin-field-hint">12–16 characters. Click “Generate strong password” for a random one.</span>
                        @error('temporary_password')<span class="admin-field-error" id="error-temporary_password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
                        @include('auth.partials.password-requirements', ['passwordTarget' => 'temporary_password'])
                    </div>

                    <label class="admin-field">
                        <span>Confirm password <span class="required-mark" aria-hidden="true">*</span></span>
                        <input type="password" name="temporary_password_confirmation" class="admin-input" autocomplete="new-password" data-method-field="manual_password">
                        @error('temporary_password_confirmation')<span class="admin-field-error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>{{ $message }}</span>@enderror
                    </label>

                    <div class="staff-manual-notice" role="note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span>Share this password securely. It will not be shown again after you create the account.</span>
                    </div>
                </div>
            </fieldset>

            <div class="admin-form-actions staff-create-actions">
                <button type="submit" class="admin-button admin-button--primary" id="staff-create-submit">Create and email temporary password</button>
                <a href="{{ route('admin.staff.index') }}" class="admin-button admin-button--secondary">Cancel</a>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('staff-create-form');
  if (!form) return;

  var labels = {
    invitation: 'Create and send invitation',
    emailed_temp_password: 'Create and email temporary password',
    manual_password: 'Create account'
  };
  var submit = document.getElementById('staff-create-submit');
  var panels = form.querySelectorAll('[data-panel]');
  var radios = form.querySelectorAll('input[name="creation_method"]');

  function selectedMethod() {
    var checked = form.querySelector('input[name="creation_method"]:checked');
    return checked ? checked.value : 'emailed_temp_password';
  }

  function syncMethod() {
    var method = selectedMethod();
    if (submit) submit.textContent = labels[method] || labels.emailed_temp_password;
    panels.forEach(function (panel) {
      var active = panel.getAttribute('data-panel') === method;
      panel.hidden = !active;
      panel.querySelectorAll('[data-method-field]').forEach(function (field) {
        field.disabled = !active;
        if (!active) field.removeAttribute('required');
        else if (field.name === 'invitation_expires_in_hours' || field.name === 'temporary_password' || field.name === 'temporary_password_confirmation') field.setAttribute('required', '');
      });
    });
    validate();
  }

  function passwordOk() {
    var method = selectedMethod();
    if (method !== 'manual_password') return true;
    var pw = document.getElementById('temporary_password');
    var confirm = form.querySelector('input[name="temporary_password_confirmation"]');
    if (!pw || !confirm) return false;
    var v = pw.value;
    return v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && v === confirm.value && v !== '';
  }

  function validate() {
    if (!submit) return;
    // form.checkValidity() respects disabled/hidden fields, so conditional
    // fields only count when their method is selected.
    var valid = form.checkValidity() && passwordOk();
    submit.disabled = !valid;
    submit.setAttribute('aria-disabled', valid ? 'false' : 'true');
  }

  radios.forEach(function (radio) { radio.addEventListener('change', syncMethod); });
  form.addEventListener('input', validate);
  form.addEventListener('change', validate);

  // Show / hide toggle
  form.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-password-toggle'));
      if (!input) return;
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      var label = btn.querySelector('span');
      if (label) label.textContent = showing ? 'Show' : 'Hide';
      input.focus();
    });
  });

  // Generate strong password (12-16 chars, crypto-random)
  var generateBtn = document.getElementById('generate-password-btn');
  var passwordInput = document.getElementById('temporary_password');
  var confirmInput = form.querySelector('input[name="temporary_password_confirmation"]');
  function generate() {
    var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ', lower = 'abcdefghijkmnpqrstuvwxyz', digits = '23456789';
    var all = upper + lower + digits;
    var length = 12 + Math.floor(Math.random() * 5); // 12-16
    function pick(chars) {
      var buf = new Uint32Array(1);
      window.crypto.getRandomValues(buf);
      return chars[buf[0] % chars.length];
    }
    var out = pick(upper) + pick(lower) + pick(digits);
    for (var i = 3; i < length; i++) out += pick(all);
    out = out.split('').sort(function () { return Math.random() - 0.5; }).join('');
    if (passwordInput) {
      passwordInput.value = out;
      passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (confirmInput) {
      confirmInput.value = out;
      confirmInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    validate();
  }
  if (generateBtn) generateBtn.addEventListener('click', generate);

  // Copy buttons (manual password + success screen share data-copy-target)
  function copyText(text, btn) {
    function done() {
      if (!btn) return;
      var original = btn.textContent;
      btn.textContent = 'Copied';
      setTimeout(function () { btn.textContent = original; }, 1600);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () { fallback(); });
    } else { fallback(); }
    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta); done();
    }
  }
  var copyBtn = document.getElementById('copy-password-btn');
  if (copyBtn) copyBtn.addEventListener('click', function () {
    if (passwordInput && passwordInput.value) copyText(passwordInput.value, copyBtn);
  });
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-copy-target'));
      if (el) copyText(el.textContent.trim(), btn);
    });
  });

  syncMethod();
})();
</script>
@endpush
