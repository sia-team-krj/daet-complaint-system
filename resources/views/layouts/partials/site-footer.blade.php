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
--}}
<footer class="site-footer">
    <p class="site-footer__id">
        <strong>Municipality of Daet</strong> &nbsp;&middot;&nbsp; Camarines Norte
        &nbsp;&middot;&nbsp; Official Transparency Complaint Portal
    </p>

    {{-- The Transparency Register is withheld from administrators, matching the
         navbar. They run the workspace rather than the public register, and
         TransparencyPageTest asserts the link is absent from their shell. The
         legal documents are shown to everyone - an operator is still bound by
         them and needs to be able to read them. --}}
    <nav class="site-footer__legal" aria-label="Legal and public information">
        <a href="{{ route('terms') }}" wire:navigate>Terms &amp; Conditions</a>
        <a href="{{ route('privacy') }}" wire:navigate>Privacy Policy</a>
        @if(auth()->user()?->role !== 'admin')
            <a href="{{ route('transparency') }}" wire:navigate>Transparency Register</a>
        @endif
    </nav>

    <span class="site-footer__seal">Republic Act 6713 &nbsp;&middot;&nbsp; FOI Compliant</span>
</footer>

<style>
    .site-footer {
        position: relative;
        z-index: 1;
        background: #060f1e;
        border-top: 1px solid rgba(201, 168, 76, 0.14);
        padding: 18px clamp(20px, 3vw, 48px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px 24px;
    }

    .site-footer__id {
        margin: 0;
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.24);
        letter-spacing: 0.06em;
    }

    .site-footer__id strong {
        color: rgba(201, 168, 76, 0.5);
    }

    .site-footer__legal {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    /* Contrast is deliberately restrained: these are quiet reference links, not
       calls to action. They still clear 4.5:1 against the footer fill, and the
       hover state is what signals interactivity rather than resting colour. */
    .site-footer__legal a {
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.45);
        text-decoration: none;
        transition: color 0.18s ease;
    }

    .site-footer__legal a:hover,
    .site-footer__legal a:focus-visible {
        color: rgba(226, 192, 106, 0.95);
    }

    .site-footer__legal a:focus-visible {
        outline: 1px solid rgba(201, 168, 76, 0.7);
        outline-offset: 3px;
    }

    .site-footer__seal {
        font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: 10px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.16);
    }

    @media (max-width: 600px) {
        .site-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 20px 18px;
        }

        .site-footer__legal {
            gap: 6px 16px;
        }
    }
</style>