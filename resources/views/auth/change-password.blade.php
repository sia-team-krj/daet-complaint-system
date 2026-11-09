@extends('layouts.guest')
@section('title', 'Set a new password - Daet Listens')
@section('content')
<div class="auth-root">
  <div class="auth-decorations">
    <div class="auth-bg"></div>
    <div class="auth-glow-tr"></div>
    <div class="auth-glow-bl"></div>
    <div class="auth-wm">DAET</div>
  </div>

  <div class="auth-layout auth-layout-centered">
    <div class="auth-form-panel">
      <div class="auth-form-wrap">

        <div class="auth-form-eyebrow fu d1">First sign-in</div>
        <h1 class="auth-form-heading fu d2">Set a new password</h1>
        <p class="auth-form-sub fu d2">
          You signed in with a temporary password. Choose a new password to continue — you will not be able to use the temporary one again.
        </p>

        @if ($errors->any())
          <div class="auth-alert alert-error fu">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif

        <form data-loading-submit method="POST" action="{{ route('password.change.store') }}">
          @csrf

          <div class="field-group fu d3">
            <label class="field-label" for="new_password">New password</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input id="new_password" name="new_password" type="password" class="field-input" placeholder="Minimum 8 characters" required autocomplete="new-password">
              <div class="field-underline"></div>
            </div>
            @include('auth.partials.password-requirements', ['passwordTarget' => 'new_password'])
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="new_password_confirmation">Confirm new password</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
              <input id="new_password_confirmation" name="new_password_confirmation" type="password" class="field-input" placeholder="Re-enter new password" required autocomplete="new-password">
              <div class="field-underline"></div>
            </div>
          </div>

          <button type="submit" class="btn-submit fu d5">
            Set new password
          </button>
        </form>
      </div>
    </div>
  </div>

  @include('layouts.partials.site-footer')
</div>
@endsection

@section('site-footer')
    <span hidden aria-hidden="true"></span>
@endsection

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  :root {
    --navy: #142D4F; --navy-mid: #1B3A61; --gold: #C9A84C; --gold-light: #E2C06A;
    --white: #ffffff; --text-dim: rgba(255,255,255,0.55); --border-gold: rgba(201,168,76,0.20);
  }
  .auth-root, .auth-root * { box-sizing: border-box; }
  .auth-root { width:100%; max-width:100%; min-width:0; font-family:'DM Sans',sans-serif; padding-top:var(--navbar-height); min-height:100svh; background:var(--navy); position:relative; display:flex; flex-direction:column; }
  .auth-decorations { position:absolute; inset:0; overflow:hidden; pointer-events:none; z-index:0; }
  .auth-bg { position:absolute; inset:0; background-image:repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px); }
  .auth-glow-tr { position:absolute; top:-20%; right:-8%; width:50vw; height:50vw; max-width:700px; max-height:700px; background:radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%); }
  .auth-glow-bl { position:absolute; bottom:-20%; left:-8%; width:40vw; height:40vw; max-width:560px; max-height:560px; background:radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 70%); }
  .auth-wm { position:absolute; bottom:-4%; right:0; font-family:'Cormorant Garamond',serif; font-size:clamp(80px,18vw,280px); font-weight:700; color:rgba(255,255,255,0.018); line-height:1; user-select:none; letter-spacing:-0.02em; }
  .auth-layout-centered { position:relative; z-index:2; width:100%; flex:1; display:flex; align-items:center; justify-content:center; padding:clamp(40px,5vh,80px) clamp(20px,3vw,48px); }
  .auth-form-panel { display:flex; flex-direction:column; justify-content:center; align-items:center; width:100%; }
  .auth-form-wrap { width:100%; max-width:440px; }
  .auth-form-eyebrow { display:inline-flex; align-items:center; gap:12px; font-size:10px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase; color:var(--gold); margin-bottom:10px; }
  .auth-form-eyebrow::before { content:''; width:24px; height:1px; background:var(--gold); }
  .auth-form-heading { font-family:'Cormorant Garamond',serif; font-size:clamp(28px,2.8vw,42px); font-weight:700; color:var(--white); line-height:1.08; margin-bottom:8px; }
  .auth-form-sub { font-size:13px; color:var(--text-dim); font-weight:300; line-height:1.7; margin-bottom:28px; }
  .field-group { margin-bottom:16px; }
  .field-label { display:block; font-size:10.5px; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; color:rgba(255,255,255,0.55); margin-bottom:7px; }
  .field-wrap { position:relative; }
  .field-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:rgba(201,168,76,0.85); pointer-events:none; }
  .field-input { width:100%; background:#1F3D60; border:1px solid rgba(255,255,255,0.22); border-radius:4px; padding:12px 14px 12px 42px; font-family:'DM Sans',sans-serif; font-size:13px; color:#ffffff; outline:none; }
  .field-input::placeholder { color:rgba(255,255,255,0.55); opacity:1; }
  .field-input:focus { border-color:#D4A947; outline:none; box-shadow:0 0 0 3px rgba(212,169,71,0.2); }
  .field-underline { position:absolute; bottom:0; left:0; width:100%; height:2px; background:linear-gradient(90deg,var(--gold),transparent); transform:scaleX(0); transform-origin:left; transition:transform 0.35s ease; }
  .field-input:focus ~ .field-underline { transform:scaleX(1); }
  .btn-submit { width:100%; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--navy); font-family:'DM Sans',sans-serif; font-size:12px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; padding:15px 32px; border-radius:4px; border:none; cursor:pointer; }
  .auth-alert { border-radius:4px; padding:12px 16px; font-size:12.5px; line-height:1.6; margin-bottom:18px; }
  .alert-error { background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); color:#fca5a5; }
  @keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
  .fu{animation:fadeUp 0.7s cubic-bezier(.22,.68,0,1) both}.d1{animation-delay:0.04s}.d2{animation-delay:0.16s}.d3{animation-delay:0.28s}.d4{animation-delay:0.40s}.d5{animation-delay:0.52s}
  @media (max-width:768px){ .auth-wm{display:none} .auth-layout-centered{padding:48px 20px 80px} }
</style>
