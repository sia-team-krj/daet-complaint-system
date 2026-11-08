{{--
    The site footer, in one place.

    Standing legal documents live here (Terms, Privacy, Transparency Register).
    Self-contained markup and styles so every shell that renders it gets the same
    look. A page that renders this itself declares `@section('site-footer')`.

    Content: brand + admin-editable footer_text/contact_text (SystemSetting) +
    legal links + seal/copyright. Visual language: navy-950 fill, gold-500 top
    accent, Inter type, tokens only.
--}}
<footer class="site-footer">
  <div class="site-footer__inner">
    <div class="site-footer__brand">
      <img src="/images/lgulogo.webp" alt="" class="site-footer__logo" width="36" height="36" aria-hidden="true">
      <div class="site-footer__brand-text">
        <strong class="site-footer__name">{{ __('footer.municipality') }}</strong>
        <span class="site-footer__tagline">{{ \App\Models\SystemSetting::getValue('footer_text', 'Municipality of Daet · Camarines Norte · Official Transparency Complaint Portal') }}</span>
      </div>
    </div>

    <nav class="site-footer__legal" aria-label="{{ __('footer.legal_nav') }}">
        <a href="{{ route('terms') }}" wire:navigate>{{ __('footer.terms') }}</a>
        <a href="{{ route('privacy') }}" wire:navigate>{{ __('footer.privacy') }}</a>
        @if(auth()->user()?->role !== 'admin')
            <a href="{{ route('transparency') }}" wire:navigate>{{ __('footer.transparency_register') }}</a>
        @endif
    </nav>

    <div class="site-footer__meta">
      <p class="site-footer__contact">{{ \App\Models\SystemSetting::getValue('contact_text', 'For concerns, contact the Municipality of Daet through the Transparency Register or visit the municipal hall.') }}</p>
    </div>

    <div class="site-footer__bottom">
      <span class="site-footer__seal">{{ __('footer.seal') }}</span>
      <span class="site-footer__copy">&copy; {{ date('Y') }} {{ __('footer.municipality') }}</span>
    </div>
  </div>
</footer>

<style>
  .site-footer {
      position: relative;
      z-index: 1;
      background: var(--navy-950);
      border-top: 2px solid var(--gold-500);
  }

  .site-footer__inner {
      width: 100%;
      max-width: 1040px;
      margin: 0 auto;
      padding: 24px 24px 20px;
      display: grid;
      grid-template-columns: 1fr auto;
      grid-template-areas:
          "brand legal"
          "meta meta"
          "bottom bottom";
      gap: 12px 24px;
      box-sizing: border-box;
      align-items: center;
  }

  .site-footer__brand {
      grid-area: brand;
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
  }

  .site-footer__logo {
      width: 36px;
      height: 36px;
      object-fit: contain;
      flex-shrink: 0;
      border-radius: var(--radius-sm);
  }

  .site-footer__brand-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
  }

  .site-footer__name {
      margin: 0;
      font-family: var(--font-sans);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--surface);
  }

  .site-footer__tagline {
      margin: 0;
      font-family: var(--font-sans);
      font-size: 11px;
      letter-spacing: 0.04em;
      color: rgba(255, 255, 255, 0.68);
  }

  .site-footer__legal {
      grid-area: legal;
      display: flex;
      align-items: center;
      gap: 4px 16px;
      flex-wrap: wrap;
      justify-content: flex-end;
  }

  .site-footer__legal a {
      font-family: var(--font-sans);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.78);
      text-decoration: none;
      padding: 8px 2px;
      min-height: 40px;
      display: inline-flex;
      align-items: center;
      transition: color 0.18s ease;
  }

  .site-footer__legal a:hover {
      color: var(--gold-300);
  }

  .site-footer__legal a:focus-visible {
      color: var(--gold-300);
      outline: 2px solid var(--gold-300);
      outline-offset: 3px;
      border-radius: 2px;
  }

  .site-footer__meta {
      grid-area: meta;
      padding-top: 4px;
  }

  .site-footer__contact {
      margin: 0;
      font-family: var(--font-sans);
      font-size: 12px;
      line-height: 1.6;
      color: #9fb3d1;
  }

  .site-footer__bottom {
      grid-area: bottom;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px 16px;
      flex-wrap: wrap;
      padding-top: 12px;
      border-top: 1px solid rgba(255, 255, 255, 0.12);
  }

  .site-footer__seal {
      font-family: var(--font-sans);
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #9fb3d1;
  }

  .site-footer__copy {
      font-family: var(--font-sans);
      font-size: 10px;
      letter-spacing: 0.06em;
      color: #9fb3d1;
  }

  @media (max-width: 768px) {
      .site-footer__inner {
          grid-template-columns: 1fr;
          grid-template-areas:
              "brand"
              "legal"
              "meta"
              "bottom";
          padding: 20px 16px;
          gap: 12px;
      }

      .site-footer__legal {
          justify-content: flex-start;
          gap: 2px 12px;
      }

      .site-footer__legal a {
          min-height: 44px;
      }

      .site-footer__bottom {
          flex-direction: column;
          align-items: flex-start;
          gap: 4px;
      }
  }

  @media (prefers-reduced-motion: reduce) {
      .site-footer__legal a { transition: none; }
  }
</style>