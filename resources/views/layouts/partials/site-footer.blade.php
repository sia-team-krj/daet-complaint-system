{{--
    The site footer, in one place.

    This is where the standing legal documents live. They were briefly in the
    navbar, which was wrong: the bar is the primary wayfinding surface and two
    more items crowded the links a resident actually uses, on top of pushing the
    account controls into the compact menu sooner. Legal notices belong in the
    footer, where they are always present but never in the way.

    Self-contained markup and styles on purpose. Four shells render it - the
    public layout, the authenticated layout, and the pages that place it inside
    their own full-height wrapper - and each of those pages has its own scoped
    stylesheet, so relying on a class defined elsewhere would render unstyled in
    one of them.

    A page that renders this itself declares `@section('site-footer')` so the
    shells know the page owns its footer and do not add a second. That section
    must contain something - `@hasSection` compiles to a non-empty content
    check, so an empty section is treated as absent.

    The footer is full-bleed, but its inner container shares the standard
    1040px max-width and 24px / 16px horizontal padding with the page content,
    so header, content and footer edges line up at every width. Text colours
    are kept at WCAG AA (4.5:1) or better against the #060f1e fill — the
    previous rgba(255,255,255,0.16–0.45) values were near-invisible on navy.

    Labels come from lang/{locale}/footer.php, so the footer follows the same
    locale as the rest of the portal (English by default). It is never
    hardcoded to a single language.
--}}
<footer class="site-footer">
  <div class="site-footer__inner">
    <p class="site-footer__id">
        <strong>{{ __('footer.municipality') }}</strong> &nbsp;&middot;&nbsp; {{ __('footer.province') }}
        &nbsp;&middot;&nbsp; {{ __('footer.portal') }}
    </p>

    {{-- The Transparency Register is withheld from administrators, matching the
         navbar. They run the workspace rather than the public register, and
         TransparencyPageTest asserts the link is absent from their shell. The
         legal documents are shown to everyone - an operator is still bound by
         them and needs to be able to read them. --}}
    <nav class="site-footer__legal" aria-label="{{ __('footer.legal_nav') }}">
        <a href="{{ route('terms') }}" wire:navigate>{{ __('footer.terms') }}</a>
        <a href="{{ route('privacy') }}" wire:navigate>{{ __('footer.privacy') }}</a>
        @if(auth()->user()?->role !== 'admin')
            <a href="{{ route('transparency') }}" wire:navigate>{{ __('footer.transparency_register') }}</a>
        @endif
    </nav>

    @if(!auth()->check() || auth()->user()->role === 'citizen')
        <div class="site-footer__language">
            @include('layouts.partials.language-switcher', ['variant' => 'footer'])
        </div>
    @endif

    <span class="site-footer__seal">{{ __('footer.seal') }}</span>
  </div>
</footer>

<style>
    .site-footer {
        position: relative;
        z-index: 1;
        background: #060f1e;
        border-top: 1px solid rgba(201, 168, 76, 0.14);
    }

    .site-footer__inner {
        width: 100%;
        max-width: 1040px;
        margin: 0 auto;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px 24px;
        box-sizing: border-box;
    }

    .site-footer__id {
        margin: 0;
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.72);
        letter-spacing: 0.06em;
    }

    .site-footer__id strong {
        color: #E2C06A;
    }

    .site-footer__legal {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .site-footer__language {
        flex: 0 0 auto;
        min-width: 210px;
    }

    .site-footer__legal a {
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.78);
        text-decoration: none;
        transition: color 0.18s ease;
    }

    .site-footer__legal a:hover,
    .site-footer__legal a:focus-visible {
        color: #E2C06A;
    }

    .site-footer__legal a:focus-visible {
        outline: 2px solid #E2C06A;
        outline-offset: 3px;
    }

    .site-footer__seal {
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 10px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.72);
    }

    @media (max-width: 768px) {
        .site-footer__inner {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 20px 16px;
        }

        .site-footer__legal {
            gap: 6px 16px;
        }

        .site-footer__language {
            width: 100%;
            min-width: 0;
        }
    }
</style>
