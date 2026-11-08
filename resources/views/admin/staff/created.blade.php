@extends('admin.layouts.app')

@section('title', 'Staff account created - Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow staff-create-page">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.staff.index') }}">Staff accounts</a>
        <span aria-hidden="true">/</span>
        <span>Account created</span>
    </nav>

    <header class="admin-page-header staff-create-header">
        <div>
            <h1 class="admin-page-title">Account created</h1>
            <p class="admin-page-description">Copy the temporary password now. It will not be shown again.</p>
        </div>
    </header>

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">Temporary password — shown once</h2>
        </div>
        <div class="admin-panel-body admin-form-stack">
            <div class="staff-created-summary">
                <p><strong>Account email:</strong> <span id="staff-created-email">{{ $email }}</span></p>
                <p><strong>Name:</strong> {{ $full_name }}</p>
                @if(!empty($department_name))
                    <p><strong>Department:</strong> {{ $department_name }}</p>
                @endif
                @if(!empty($expires_at))
                    <p><strong>Temporary password expires:</strong> {{ $expires_at }}</p>
                @endif
                <p>The staff member must set a new password on first sign-in.</p>
            </div>

            <label class="admin-field">
                <span>Temporary password</span>
                <span class="staff-created-password-row">
                    <code class="staff-created-password" id="staff-created-password">{{ $temporary_password }}</code>
                    <button type="button" class="admin-button admin-button--secondary" data-copy-target="staff-created-password">Copy</button>
                </span>
            </label>

            <div class="staff-manual-notice" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <span>Share this password securely. It will not be shown again after you create the account.</span>
            </div>

            <div class="admin-form-actions staff-create-actions">
                <a href="{{ route('admin.staff.index') }}" class="admin-button admin-button--primary">Back to staff accounts</a>
                <a href="{{ route('admin.staff.create') }}" class="admin-button admin-button--secondary">Create another account</a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
  function copyText(text, btn) {
    function done() {
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
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-copy-target'));
      if (el) copyText(el.textContent.trim(), btn);
    });
  });
})();
</script>
@endpush
