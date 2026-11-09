@extends('admin.layouts.app')
@section('title', __('admin_settings.title').' - Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow staff-create-page">
  <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin</a><span aria-hidden="true">/</span><span>{{ __('admin_settings.title') }}</span></nav>
  <header class="admin-page-header"><div><h1 class="admin-page-title">{{ __('admin_settings.title') }}</h1><p class="admin-page-description">{{ __('admin_settings.subtitle') }}</p></div></header>
  @include('admin.partials.flash')

  <section class="admin-panel">
    <div class="admin-panel-heading"><h2 class="admin-panel-title">{{ __('admin_settings.title') }}</h2></div>
    <form data-loading-submit method="POST" action="{{ route('admin.settings.update') }}" class="admin-panel-body admin-form-stack">
      @csrf @method('PUT')
      <label class="admin-field"><span>{{ __('admin_settings.default_language') }}</span>
        <select name="default_locale" class="admin-input">
          @foreach(config('localization.supported') as $code=>$label)<option value="{{ $code }}" @selected(($settings['default_locale'] ?? 'en') === $code)>{{ $label }}</option>@endforeach
        </select>
      </label>
      <div class="admin-form-grid">
        <label class="admin-field"><span>{{ __('admin_settings.overdue_days') }}</span><input type="number" name="overdue_days" class="admin-input" value="{{ $settings['overdue_days'] ?? 5 }}" min="1" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.waiting_reminder_days') }}</span><input type="number" name="waiting_reminder_days" class="admin-input" value="{{ $settings['waiting_reminder_days'] ?? 3 }}" min="1" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.invitation_expires_in_hours') }}</span><input type="number" name="invitation_expires_in_hours" class="admin-input" value="{{ $settings['invitation_expires_in_hours'] ?? 48 }}" min="1" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.temp_password_expires_in_hours') }}</span><input type="number" name="temp_password_expires_in_hours" class="admin-input" value="{{ $settings['temp_password_expires_in_hours'] ?? 72 }}" min="1" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.password_min_length') }}</span><input type="number" name="password_min_length" class="admin-input" value="{{ $settings['password_min_length'] ?? 8 }}" min="8" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.session_timeout_minutes') }}</span><input type="number" name="session_timeout_minutes" class="admin-input" value="{{ $settings['session_timeout_minutes'] ?? 120 }}" min="15" required></label>
      </div>
      <label class="admin-field"><span>{{ __('admin_settings.sender_name') }}</span><input type="text" name="sender_name" class="admin-input" value="{{ $settings['sender_name'] ?? 'Daet Listens' }}" required></label>
      <label class="admin-field"><span>{{ __('admin_settings.subject_staff_welcome') }}</span><input type="text" name="subject_staff_welcome" class="admin-input" value="{{ $settings['subject_staff_welcome'] ?? '' }}"></label>
      <label class="admin-field"><span>{{ __('admin_settings.subject_invitation') }}</span><input type="text" name="subject_invitation" class="admin-input" value="{{ $settings['subject_invitation'] ?? '' }}"></label>
      <label class="admin-field"><span>{{ __('admin_settings.subject_reset') }}</span><input type="text" name="subject_reset" class="admin-input" value="{{ $settings['subject_reset'] ?? '' }}"></label>
      <label class="admin-field"><span>{{ __('admin_settings.footer_text') }}</span><textarea name="footer_text" class="admin-input admin-textarea">{{ $settings['footer_text'] ?? '' }}</textarea></label>
      <label class="admin-field"><span>{{ __('admin_settings.contact_text') }}</span><textarea name="contact_text" class="admin-input admin-textarea">{{ $settings['contact_text'] ?? '' }}</textarea></label>
      <label class="admin-field"><span>{{ __('admin_settings.audit_retention_days') }}</span><input type="number" name="audit_retention_days" class="admin-input" value="{{ $settings['audit_retention_days'] ?? 365 }}" min="30" required></label>
      @php $barangays = json_decode($settings['barangays_json'] ?? '[]', true) ?: \App\Models\User::BARANGAYS; @endphp
      <label class="admin-field"><span>{{ __('admin_settings.barangays') }}</span><span class="admin-field-hint">{{ __('admin_settings.barangays_hint') }}</span><textarea name="barangays" class="admin-input admin-textarea" rows="8">{{ implode("\n", $barangays) }}</textarea></label>
      <div class="admin-form-actions"><button type="submit" class="admin-button admin-button--primary">{{ __('admin_settings.save') }}</button></div>
    </form>
  </section>

  <section class="admin-panel">
    <div class="admin-panel-heading"><h2 class="admin-panel-title">{{ __('admin_settings.departments') }}</h2></div>
    <div class="admin-panel-body">
      <p>{{ __('admin_settings.departments_hint') }}</p>
      <a href="{{ route('admin.departments.index') }}" class="admin-button admin-button--secondary">{{ __('admin_settings.departments') }}</a>
    </div>
  </section>

  <section class="admin-panel">
    <div class="admin-panel-heading"><h2 class="admin-panel-title">{{ __('admin_settings.categories') }}</h2></div>
    <div class="admin-panel-body admin-form-stack">
      <p>{{ __('admin_settings.categories_hint') }}</p>
      <form data-loading-submit method="POST" action="{{ route('admin.settings.categories.store') }}" class="admin-form-grid">
        @csrf
        <label class="admin-field"><span>{{ __('admin_settings.key') }}</span><input type="text" name="key" class="admin-input" required placeholder="road_damage"></label>
        <label class="admin-field"><span>{{ __('admin_settings.label_en') }}</span><input type="text" name="label_en" class="admin-input" required></label>
        <label class="admin-field"><span>{{ __('admin_settings.label_fil') }}</span><input type="text" name="label_fil" class="admin-input"></label>
        <button type="submit" class="admin-button admin-button--primary">{{ __('admin_settings.add') }}</button>
      </form>
      <ul>
        @foreach($categories as $cat)
          <li>
            <form data-loading-submit method="POST" action="{{ route('admin.settings.categories.update', $cat) }}" class="admin-form-grid">
              @csrf @method('PUT')
              <span>{{ $cat->key }} — {{ $cat->label() }} ({{ $cat->is_active ? __('admin_settings.active') : __('admin_settings.deactivate') }})</span>
              <label class="admin-field"><span>{{ __('admin_settings.label_en') }}</span><input type="text" name="label_en" class="admin-input" value="{{ $cat->label_en }}" required></label>
              <label class="admin-field"><span>{{ __('admin_settings.label_fil') }}</span><input type="text" name="label_fil" class="admin-input" value="{{ $cat->label_fil }}"></label>
              <label><input type="checkbox" name="is_active" value="1" @checked($cat->is_active)> {{ __('admin_settings.active') }}</label>
              <button type="submit" class="admin-button admin-button--secondary">{{ __('admin_settings.save') }}</button>
            </form>
            <form data-loading-submit method="POST" action="{{ route('admin.settings.categories.toggle', $cat) }}">@csrf<button type="submit" class="admin-button admin-button--secondary">{{ $cat->is_active ? __('admin_settings.deactivate') : __('admin_settings.activate') }}</button></form>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
</div>
@endsection
