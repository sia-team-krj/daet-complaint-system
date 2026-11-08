@extends('layouts.app')
@section('title', __('settings.title').' - Daet Listens')
@section('content')
<div class="settings-page">
  <div class="settings-container">
    <header class="settings-header">
      <h1>{{ __('settings.title') }}</h1>
      <p>{{ __('settings.subtitle') }}</p>
    </header>

    <nav class="settings-tabs" aria-label="{{ __('settings.title') }}">
      <ul role="tablist">
        @foreach(['profile','security','notifications','preferences'] as $t)
          <li role="presentation"><a href="{{ route('settings', ['tab' => $t]) }}" role="tab" aria-selected="{{ $tab === $t ? 'true' : 'false' }}" class="settings-tab {{ $tab === $t ? 'is-active' : '' }}" @if($tab === $t) aria-current="page" @endif data-settings-tab="{{ $t }}">{{ __('settings.tabs.'.$t) }}</a></li>
        @endforeach
        @if(auth()->user()->role === 'citizen')
          <li role="presentation"><a href="{{ route('settings', ['tab' => 'privacy']) }}" role="tab" aria-selected="{{ $tab === 'privacy' ? 'true' : 'false' }}" class="settings-tab {{ $tab === 'privacy' ? 'is-active' : '' }}" @if($tab === 'privacy') aria-current="page" @endif data-settings-tab="privacy">{{ __('settings.privacy.title') }}</a></li>
        @endif
      </ul>
    </nav>

    <div class="settings-toast" role="status" aria-live="polite" id="settings-toast" hidden></div>
    @if(session('status') && !request()->expectsJson())
      <div class="settings-toast" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="settings-errors" role="alert" aria-live="assertive">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
      </div>
    @endif

    @if($tab === 'profile')
      <section class="settings-panel" role="tabpanel" aria-labelledby="tab-profile" id="tab-profile">
        <h2>{{ __('settings.profile.title') }}</h2>
        <form method="POST" action="{{ route('settings.profile.update') }}" data-settings-form>
          @csrf
          <div class="settings-grid">
            <label class="admin-field" for="settings-first-name"><span>{{ __('settings.profile.first_name') }} <span class="required-mark" aria-hidden="true">*</span></span><input id="settings-first-name" type="text" name="first_name" class="admin-input" value="{{ old('first_name', $user->first_name) }}" required></label>
            <label class="admin-field" for="settings-last-name"><span>{{ __('settings.profile.last_name') }} <span class="required-mark" aria-hidden="true">*</span></span><input id="settings-last-name" type="text" name="last_name" class="admin-input" value="{{ old('last_name', $user->last_name) }}" required></label>
          </div>
          <label class="admin-field field-narrow" for="settings-email"><span>{{ __('settings.profile.email') }} <span class="required-mark" aria-hidden="true">*</span></span><input id="settings-email" type="email" name="email" class="admin-input" value="{{ old('email', $user->email) }}" required></label>
          <label class="admin-field field-narrow" for="settings-contact"><span>{{ __('settings.profile.contact_number') }}</span><input id="settings-contact" type="text" name="contact_number" class="admin-input" value="{{ old('contact_number', $user->contact_number) }}" inputmode="numeric" placeholder="09171234567"></label>
          @if($user->role === 'citizen')
            <label class="admin-field field-narrow" for="settings-barangay"><span>{{ __('settings.profile.barangay') }}</span>
              <select id="settings-barangay" name="barangay" class="admin-input">
                <option value="">—</option>
                @foreach(\App\Models\User::BARANGAYS as $b)
                  <option value="{{ $b }}" @selected(old('barangay', $user->barangay) === $b)>{{ $b }}</option>
                @endforeach
              </select>
            </label>
          @endif
          @if($user->role === 'staff')
            <div class="admin-field field-narrow"><span>{{ __('settings.profile.department') }}</span><input type="text" class="admin-input" value="{{ $user->department?->name ?? '—' }}" disabled><span class="admin-field-hint">{{ __('settings.profile.department_readonly') }}</span></div>
          @endif
          <div class="settings-form-actions"><button type="submit" class="admin-button admin-button--primary" data-save-button>{{ __('settings.profile.save') }}</button></div>
        </form>
      </section>
    @endif

    @if($tab === 'security')
      <section class="settings-panel" role="tabpanel" id="tab-security">
        <h2>{{ __('settings.security.title') }}</h2>
        <form method="POST" action="{{ route('settings.password.update') }}" data-settings-form>
          @csrf
          <h3>{{ __('settings.security.change_password') }}</h3>
          <label class="admin-field field-narrow" for="settings-current"><span>{{ __('settings.security.current_password') }} <span class="required-mark" aria-hidden="true">*</span></span>
            <span class="settings-password-wrap"><input id="settings-current" type="password" name="current_password" class="admin-input" required autocomplete="current-password" @error('current_password') aria-invalid="true" aria-describedby="error-current" @enderror><button type="button" class="admin-button admin-button--secondary settings-password-toggle" data-password-toggle="settings-current" aria-label="Show password" aria-pressed="false">Show</button></span>
            @error('current_password')<span class="admin-field-error" id="error-current" role="alert">{{ $message }}</span>@enderror
          </label>
          <label class="admin-field field-narrow" for="settings_new_password"><span>{{ __('settings.security.new_password') }} <span class="required-mark" aria-hidden="true">*</span></span>
            <span class="settings-password-wrap"><input id="settings_new_password" type="password" name="new_password" class="admin-input" required autocomplete="new-password" @error('new_password') aria-invalid="true" aria-describedby="error-new" @enderror><button type="button" class="admin-button admin-button--secondary settings-password-toggle" data-password-toggle="settings_new_password" aria-label="Show password" aria-pressed="false">Show</button></span>
            @error('new_password')<span class="admin-field-error" id="error-new" role="alert">{{ $message }}</span>@enderror
          </label>
          @include('auth.partials.password-requirements', ['passwordTarget' => 'settings_new_password'])
          <label class="admin-field field-narrow" for="settings-confirm"><span>{{ __('settings.security.confirm_password') }} <span class="required-mark" aria-hidden="true">*</span></span>
            <span class="settings-password-wrap"><input id="settings-confirm" type="password" name="new_password_confirmation" class="admin-input" required autocomplete="new-password"><button type="button" class="admin-button admin-button--secondary settings-password-toggle" data-password-toggle="settings-confirm" aria-label="Show password" aria-pressed="false">Show</button></span>
          </label>
          <div class="settings-form-actions"><button type="submit" class="admin-button admin-button--primary" data-save-button>{{ __('settings.security.update_password') }}</button></div>
        </form>

        <h3>{{ __('settings.security.sessions') }}</h3>
        @if($sessions->isEmpty())
          <p>{{ __('settings.security.sessions_empty') }}</p>
        @else
          <ul class="settings-sessions" id="settings-sessions">
            @foreach($sessions as $index => $s)
              <li class="settings-session" @if($index >= 5) data-session-extra hidden @endif>
                <strong>{{ $s->friendly }}</strong>
                @if($s->is_current)<span class="settings-session__badge">{{ __('settings.security.this_device') }}</span>@endif
                <span class="settings-session__meta">{{ $s->ip }} · <time datetime="{{ $s->last_activity->toIso8601String() }}" title="{{ $s->last_full }}" aria-label="{{ $s->last_full }}">{{ $s->last_relative }}</time></span>
                @if(!$s->is_current)
                  <form method="POST" action="{{ route('settings.sessions.logout', $s->id) }}" style="margin-left:auto">@csrf<button type="submit" class="admin-button admin-button--secondary admin-button--compact">Log out</button></form>
                @endif
              </li>
            @endforeach
          </ul>
          @if($sessions->count() > 5)
            <button type="button" class="admin-button admin-button--secondary" id="settings-show-more">Show more</button>
          @endif
        @endif

        <form method="POST" action="{{ route('settings.logout-other-devices') }}" id="logout-other-form">
          @csrf
          <button type="button" class="admin-button admin-button--danger" id="logout-other-btn">{{ __('settings.security.logout_other_devices') }}</button>
        </form>
        <dialog id="logout-other-dialog" aria-labelledby="logout-other-title">
          <h3 id="logout-other-title">{{ __('settings.security.logout_other_devices') }}</h3>
          <p>{{ __('settings.security.logout_other_done') }}</p>
          <form method="dialog"><button class="admin-button admin-button--secondary" formmethod="dialog">Cancel</button></form>
          <form method="POST" action="{{ route('settings.logout-other-devices') }}">@csrf<button type="submit" class="admin-button admin-button--danger">Confirm</button></form>
        </dialog>
      </section>
    @endif

    @if($tab === 'notifications')
      <section class="settings-panel" role="tabpanel" id="tab-notifications">
        <h2>{{ __('settings.notifications.title') }}</h2>
        <form method="POST" action="{{ route('settings.notifications.update') }}" data-settings-form>
          @csrf
          @php $prefs = $user->notification_preferences ?? ['channels'=>['in_app','email'],'events'=>$notificationEvents]; @endphp
          <fieldset><legend>{{ __('settings.notifications.channels') }}</legend>
            <div class="settings-notif-row"><div class="settings-notif-row__body"><span class="settings-notif-row__label">{{ __('settings.notifications.in_app') }}</span></div><label class="toggle-switch"><input type="checkbox" name="channels[]" value="in_app" @checked(in_array('in_app', $prefs['channels'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.in_app') }}</span></label></div>
            <div class="settings-notif-row"><div class="settings-notif-row__body"><span class="settings-notif-row__label">{{ __('settings.notifications.email') }}</span></div><label class="toggle-switch"><input type="checkbox" name="channels[]" value="email" @checked(in_array('email', $prefs['channels'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.email') }}</span></label></div>
          </fieldset>
          <fieldset><legend>{{ __('settings.notifications.events') }}</legend>
            @foreach($notificationEvents as $event)
              <div class="settings-notif-row"><div class="settings-notif-row__body"><span class="settings-notif-row__label">{{ __('settings.notifications.'.$event) }}</span><span class="settings-notif-row__desc">{{ __('settings.notifications_desc.'.$event) }}</span></div><label class="toggle-switch"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, $prefs['events'] ?? []))><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.'.$event) }}</span></label></div>
            @endforeach
          </fieldset>
          @if($user->role === 'staff')
            <label class="admin-field field-narrow" for="settings-queue"><span>{{ __('settings.notifications.default_queue_view') }}</span>
              <select id="settings-queue" name="default_queue_view" class="admin-input">
                <option value="all" @selected($user->default_queue_view === 'all')>{{ __('settings.notifications.queue_all') }}</option>
                <option value="mine" @selected($user->default_queue_view === 'mine')>{{ __('settings.notifications.queue_mine') }}</option>
                <option value="unassigned" @selected($user->default_queue_view === 'unassigned')>{{ __('settings.notifications.queue_unassigned') }}</option>
              </select>
            </label>
            <div class="settings-notif-row"><div class="settings-notif-row__body"><span class="settings-notif-row__label">{{ __('settings.notifications.daily_digest') }}</span><span class="settings-notif-row__desc">{{ __('settings.notifications.daily_digest_hint') }}</span></div><label class="toggle-switch"><input type="checkbox" name="daily_digest" value="1" @checked($user->daily_digest)><span class="toggle-switch__track"><span class="toggle-switch__knob"></span></span><span class="sr-only">{{ __('settings.notifications.daily_digest') }}</span></label></div>
          @endif
          <div class="settings-form-actions"><button type="submit" class="admin-button admin-button--primary" data-save-button>{{ __('settings.notifications.save') }}</button></div>
        </form>
      </section>
    @endif

    @if($tab === 'preferences')
      <section class="settings-panel" role="tabpanel" id="tab-preferences">
        <h2>{{ __('settings.preferences.title') }}</h2>
        <form method="POST" action="{{ route('settings.preferences.update') }}" id="preferences-form" data-preferences-autosave>
          @csrf
          <fieldset>
            <legend>{{ __('settings.preferences.language') }} <span class="required-mark" aria-hidden="true">*</span></legend>
            <div class="segmented-control" role="radiogroup" aria-label="{{ __('settings.preferences.language') }}">
              @foreach(config('localization.supported') as $code => $label)
                <label class="segmented-control__option"><input type="radio" name="locale" value="{{ $code }}" data-preference-field @checked(app()->getLocale() === $code || $user->locale === $code)> {{ $label }}</label>
              @endforeach
            </div>
            <span class="admin-field-hint">{{ __('settings.preferences.language_hint') }}</span>
          </fieldset>

          <fieldset>
            <legend>{{ __('settings.preferences.text_size') }}</legend>
            <div class="segmented-control" role="radiogroup" aria-label="{{ __('settings.preferences.text_size') }}">
              <label class="segmented-control__option"><input type="radio" name="text_size" value="normal" data-preference-field @checked($user->text_size !== 'large')><span class="segmented-control__preview">Aa</span> {{ __('settings.preferences.text_normal') }}</label>
              <label class="segmented-control__option"><input type="radio" name="text_size" value="large" data-preference-field @checked($user->text_size === 'large')><span class="segmented-control__preview" style="font-size:20px">Aa</span> {{ __('settings.preferences.text_large') }}</label>
            </div>
          </fieldset>
        </form>
      </section>
    @endif

    @if($tab === 'privacy' && $user->role === 'citizen')
      <section class="settings-panel" role="tabpanel" id="tab-privacy">
        <h2>{{ __('settings.privacy.title') }}</h2>
        <p>{{ __('settings.privacy.download_hint') }}</p>
        <div class="settings-form-actions"><a href="{{ route('settings.download-data') }}" class="admin-button admin-button--secondary">{{ __('settings.privacy.download') }}</a></div>
        <form method="POST" action="{{ route('settings.delete-account') }}" data-settings-form onsubmit="return confirm('{{ __('settings.privacy.delete_hint') }}')">
          @csrf
          <h3>{{ __('settings.privacy.delete') }}</h3>
          <p>{{ __('settings.privacy.delete_hint') }}</p>
          <p>{{ __('settings.privacy.delete_confirm') }}</p>
          <label class="admin-field field-narrow" for="delete-email"><span>{{ $user->email }}</span><input id="delete-email" type="text" name="delete_email" class="admin-input" required autocomplete="off"></label>
          <label class="admin-field field-narrow" for="delete-password"><span>{{ __('settings.security.current_password') }}</span><input id="delete-password" type="password" name="delete_password" class="admin-input" required autocomplete="current-password"></label>
          <div class="settings-form-actions"><button type="submit" class="admin-button admin-button--danger">{{ __('settings.privacy.delete_button') }}</button></div>
        </form>
      </section>
    @endif

    <div class="settings-unsaved-bar" id="settings-unsaved-bar" role="status" aria-live="polite">
      <span>You have unsaved changes</span>
      <span>
        <button type="button" class="admin-button admin-button--secondary" id="settings-discard">Discard</button>
        <button type="button" class="admin-button admin-button--primary" id="settings-save-now">Save changes</button>
      </span>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  // Tabs keyboard arrows + scroll active into view on mobile
  var tabs=[].slice.call(document.querySelectorAll('[data-settings-tab]'));
  var activeTab=document.querySelector('.settings-tab.is-active');
  if(activeTab){ try{ activeTab.scrollIntoView({block:'nearest', inline:'center'}); }catch(e){} }
  tabs.forEach(function(tab, i){
    tab.addEventListener('keydown', function(e){
      if(e.key==='ArrowRight'){ e.preventDefault(); (tabs[i+1]||tabs[0]).focus(); }
      if(e.key==='ArrowLeft'){ e.preventDefault(); (tabs[i-1]||tabs[tabs.length-1]).focus(); }
    });
  });

  // Show/hide passwords
  document.querySelectorAll('[data-password-toggle]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var input=document.getElementById(btn.getAttribute('data-password-toggle'));
      if(!input) return;
      var showing=input.type==='text';
      input.type=showing?'password':'text';
      btn.setAttribute('aria-pressed', showing?'false':'true');
      btn.textContent=showing?'Show':'Hide';
      input.focus();
    });
  });

  // Show more sessions
  var showMore=document.getElementById('settings-show-more');
  if(showMore){ showMore.addEventListener('click', function(){ document.querySelectorAll('[data-session-extra]').forEach(function(el){ el.hidden=false; }); this.hidden=true; }); }

  // Logout other devices dialog
  var logoutBtn=document.getElementById('logout-other-btn');
  var dialog=document.getElementById('logout-other-dialog');
  if(logoutBtn && dialog){ logoutBtn.addEventListener('click', function(){ if(dialog.showModal) dialog.showModal(); else if(confirm('Log out other devices?')) document.getElementById('logout-other-form').submit(); }); }

  // Unsaved bar + beforeunload
  var bar=document.getElementById('settings-unsaved-bar');
  var dirty=false;
  function markDirty(){ dirty=true; if(bar) bar.classList.add('is-visible'); }
  document.querySelectorAll('[data-settings-form]').forEach(function(f){
    f.addEventListener('input', markDirty);
    f.addEventListener('change', markDirty);
    f.addEventListener('submit', function(){ dirty=false; var b=f.querySelector('[data-save-button]'); if(b){ b.classList.add('is-loading'); b.disabled=true; } });
  });
  var discard=document.getElementById('settings-discard');
  if(discard){ discard.addEventListener('click', function(){ window.location.reload(); }); }
  var saveNow=document.getElementById('settings-save-now');
  if(saveNow){ saveNow.addEventListener('click', function(){ var f=document.querySelector('[data-settings-form] form, [data-settings-form]'); var panel=document.querySelector('[data-settings-form]'); var form=panel && panel.querySelector('form'); if(form) form.requestSubmit(); }); }
  window.addEventListener('beforeunload', function(e){ if(dirty){ e.preventDefault(); e.returnValue=''; } });

  // Preferences auto-save (immediate + toast, no Save button)
  var prefForm=document.getElementById('preferences-form');
  var toast=document.getElementById('settings-toast');
  function showToast(msg){ if(!toast) return; toast.textContent=msg; toast.hidden=false; setTimeout(function(){ toast.hidden=true; }, 3000); }
  if(prefForm){
    prefForm.querySelectorAll('[data-preference-field]').forEach(function(field){
      field.addEventListener('change', function(){
        var data=new FormData(prefForm);
        fetch(prefForm.action, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':prefForm.querySelector('input[name="_token"]').value}, body:data })
          .then(function(r){ return r.json(); })
          .then(function(json){
            showToast(json.message || 'Saved.');
            if(field.name==='locale'){ try{ localStorage.setItem('daet_locale', field.value); }catch(e){} document.documentElement.lang=field.value; setTimeout(function(){ window.location.reload(); }, 500); }
            if(field.name==='text_size'){ document.body.classList.toggle('text-size-large', field.value==='large'); }
          })
          .catch(function(){ prefForm.submit(); });
      });
    });
  }
})();
</script>
@endpush
