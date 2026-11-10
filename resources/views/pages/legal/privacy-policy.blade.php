@extends($mainLayout)

@section('title', 'Privacy Policy - Daet Listens')

{{--
    Privacy Policy for the Daet Listens complaint portal.

    Written against what the code actually does, not against what a template
    usually says. Every operational claim below is verifiable in this
    repository: the barcode `DLT-` ticket prefix, the barangay-level public
    aggregation in TransparencyController, the alias-instead-of-name reporter
    field, the audit trail columns in the activity_logs migration, and the
    consent checkbox the registration form validates. When the behaviour
    changes, this page must change with it.

    Contact details are deliberately institutional. The National Privacy
    Registrar's address is not invented here, so the grievance section states
    the internal route and leaves the external escalation to be appended by the
    Municipality.
--}}

@push('styles')
    <style>
        .legal-root {
            --lg-navy: #0b1f3a;
            --lg-navy-mid: #1B3A61;
            --lg-gold: #c9a84c;
            --lg-gold-light: #e2c06a;
            --lg-cream: #f5f0e8;
            --lg-cream-dark: #ede7d9;
            --lg-ink: #172b49;
            --lg-muted: #5b6b83;
            --lg-line: rgba(11, 31, 58, 0.12);
            min-height: 100vh;
            background: var(--lg-cream);
            color: var(--lg-ink);
            font-family: var(--font-sans);
        }

        .legal-root *,
        .legal-root *::before,
        .legal-root *::after {
            box-sizing: border-box;
        }

        .legal-masthead {
            position: relative;
            padding: calc(var(--navbar-height) + 56px) 20px 44px;
            background: linear-gradient(160deg, var(--lg-navy) 0%, var(--lg-navy-mid) 100%);
            color: #f5f0e8;
            overflow: hidden;
        }

        .legal-masthead::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(201, 168, 76, 0.025) 40px,
                rgba(201, 168, 76, 0.025) 41px
            );
            pointer-events: none;
        }

        .legal-masthead-inner {
            position: relative;
            width: min(860px, 100%);
            margin: 0 auto;
        }

        .legal-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 14px;
            padding: 6px 12px;
            color: var(--lg-gold-light);
            background: rgba(201, 168, 76, 0.12);
            border: 1px solid rgba(201, 168, 76, 0.32);
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .legal-title {
            margin: 0;
            font-family: var(--font-sans);
            font-size: clamp(30px, 5vw, 46px);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.01em;
        }

        .legal-lede {
            margin: 14px 0 0;
            max-width: 62ch;
            color: rgba(245, 240, 232, 0.68);
            font-size: 14.5px;
            line-height: 1.7;
        }

        .legal-updated {
            margin: 20px 0 0;
            color: rgba(245, 240, 232, 0.45);
            font-size: 11.5px;
            letter-spacing: 0.04em;
        }

        .legal-body {
            width: min(860px, 100%);
            margin: 0 auto;
            padding: 44px 20px 72px;
        }

        /* A table of contents is not decoration on a policy this long - it is
           how a resident finds the one section they care about. */
        .legal-toc {
            margin: 0 0 40px;
            padding: 22px 24px;
            background: #fff;
            border: 1px solid rgba(201, 168, 76, 0.32);
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(11, 31, 58, 0.05);
        }

        .legal-toc-title {
            margin: 0 0 12px;
            color: var(--lg-navy);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .legal-toc ol {
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: toc;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 6px 24px;
        }

        .legal-toc li {
            counter-increment: toc;
        }

        .legal-toc li::before {
            content: counter(toc, decimal-leading-zero);
            margin-right: 8px;
            color: var(--lg-gold);
            font-size: 11px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .legal-toc a {
            color: var(--lg-ink);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .legal-toc a:hover,
        .legal-toc a:focus-visible {
            color: var(--lg-gold);
            text-decoration: underline;
        }

        .legal-section {
            margin: 0 0 40px;
            scroll-margin-top: calc(var(--navbar-height) + 20px);
        }

        .legal-section:last-child {
            margin-bottom: 0;
        }

        .legal-section > h2 {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin: 0 0 14px;
            padding-bottom: 10px;
            color: var(--lg-navy);
            border-bottom: 1px solid var(--lg-line);
            font-family: var(--font-sans);
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .legal-section > h2::before {
            content: attr(data-num);
            color: var(--lg-gold);
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.08em;
        }

        .legal-section h3 {
            margin: 22px 0 8px;
            color: var(--lg-navy);
            font-size: 14px;
            font-weight: 700;
        }

        .legal-section p {
            margin: 0 0 14px;
            max-width: 70ch;
            color: var(--lg-muted);
            font-size: 14px;
            line-height: 1.75;
        }

        .legal-section p:last-child {
            margin-bottom: 0;
        }

        .legal-section ul {
            margin: 0 0 14px;
            padding-left: 20px;
            max-width: 70ch;
            color: var(--lg-muted);
            font-size: 14px;
            line-height: 1.75;
        }

        .legal-section li {
            margin-bottom: 8px;
        }

        .legal-section li:last-child {
            margin-bottom: 0;
        }

        .legal-section a {
            color: var(--lg-navy);
            text-decoration: underline;
            text-decoration-color: var(--lg-gold);
            text-underline-offset: 2px;
            transition: color 0.15s ease;
        }

        .legal-section a:hover,
        .legal-section a:focus-visible {
            color: var(--lg-gold);
        }

        /* Names a specific column or field, so a reader can find it in the
           system rather than guessing which data is meant. */
        .legal-field {
            padding: 1px 6px;
            background: rgba(201, 168, 76, 0.13);
            border: 1px solid rgba(201, 168, 76, 0.24);
            border-radius: 3px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12.5px;
            white-space: nowrap;
        }

        .legal-callout {
            margin: 0 0 18px;
            padding: 16px 18px;
            max-width: 70ch;
            background: rgba(201, 168, 76, 0.07);
            border: 1px solid rgba(201, 168, 76, 0.28);
            border-left: 3px solid var(--lg-gold);
            border-radius: 0 8px 8px 0;
        }

        .legal-callout p {
            margin: 0;
            color: var(--lg-ink);
            font-size: 13.5px;
            line-height: 1.7;
        }

        .legal-callout strong {
            color: var(--lg-navy);
        }

        /* A list rendered as data the reader can compare at a glance rather
           than as prose they have to parse. */
        .legal-table-wrap {
            margin: 0 0 18px;
            overflow-x: auto;
            border: 1px solid var(--lg-line);
            border-radius: 8px;
            background: #fff;
        }

        .legal-table {
            width: 100%;
            min-width: 540px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .legal-table th,
        .legal-table td {
            padding: 12px 16px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--lg-line);
        }

        .legal-table tr:last-child th,
        .legal-table tr:last-child td {
            border-bottom: 0;
        }

        .legal-table th {
            background: var(--lg-cream-dark);
            color: var(--lg-navy);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .legal-table td {
            color: var(--lg-muted);
            line-height: 1.6;
        }

        .legal-table td strong {
            display: block;
            color: var(--lg-ink);
            font-weight: 600;
        }

        .legal-cta {
            margin: 44px 0 0;
            padding: 26px;
            background: var(--lg-navy);
            border-radius: 12px;
            text-align: center;
        }

        .legal-cta h2 {
            margin: 0 0 8px;
            color: #f5f0e8;
            font-family: var(--font-sans);
            font-size: 24px;
            font-weight: 700;
        }

        .legal-cta p {
            margin: 0 0 18px;
            color: rgba(245, 240, 232, 0.62);
            font-size: 13.5px;
            line-height: 1.65;
        }

        .legal-cta-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .legal-btn {
            display: inline-flex;
            align-items: center;
            padding: 11px 22px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .legal-btn--primary {
            color: var(--lg-navy);
            background: linear-gradient(135deg, var(--lg-gold), var(--lg-gold-light));
            box-shadow: 0 3px 12px rgba(201, 168, 76, 0.28);
        }

        .legal-btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(201, 168, 76, 0.4);
        }

        .legal-btn--ghost {
            color: var(--lg-gold-light);
            border: 1px solid var(--lg-gold);
        }

        .legal-btn--ghost:hover {
            color: var(--lg-navy);
            background: var(--lg-gold-light);
        }

        @media (max-width: 600px) {
            .legal-masthead {
                padding: calc(var(--navbar-height) + 36px) 16px 32px;
            }

            .legal-body {
                padding: 30px 16px 56px;
            }

            .legal-section > h2 {
                font-size: 21px;
            }

            .legal-toc {
                padding: 18px;
            }

            .legal-toc ol {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
<div class="legal-root">
    <header class="legal-masthead">
        <div class="legal-masthead-inner">
            <p class="legal-eyebrow">Municipality of Daet &middot; Camarines Norte</p>
            <h1 class="legal-title">Privacy Policy</h1>
            <p class="legal-lede">
                This policy explains what personal information the Daet Listens complaint portal
                collects, why it collects it, who can see it, and what you can ask us to do with it.
                It applies to the complaint portal, the public transparency register, and every
                account created through them.
            </p>
            <p class="legal-updated">Last reviewed: {{ now()->format('F j, Y') }}</p>
        </div>
    </header>

    <div class="legal-body">
        <nav class="legal-toc" aria-label="Privacy Policy contents">
            <p class="legal-toc-title">On this page</p>
            <ol>
                <li><a href="#who-we-are">Who we are</a></li>
                <li><a href="#what-we-collect">Information we collect</a></li>
                <li><a href="#why">Why we use it</a></li>
                <li><a href="#legal-basis">Legal basis for processing</a></li>
                <li><a href="#public-register">What the public register shows</a></li>
                <li><a href="#who-can-see">Who can see your information</a></li>
                <li><a href="#cookies">Cookies and sessions</a></li>
                <li><a href="#retention">How long we keep it</a></li>
                <li><a href="#security">How we protect it</a></li>
                <li><a href="#your-rights">Your rights</a></li>
                <li><a href="#changes">Changes to this policy</a></li>
                <li><a href="#contact">How to reach us</a></li>
            </ol>
        </nav>

        <section class="legal-section" id="who-we-are">
            <h2 data-num="01">Who we are</h2>
            <p>
                Daet Listens is the public service complaint and transparency management system of the
                <strong>Municipality of Daet, Camarines Norte</strong>. When you file a complaint,
                register an account, or follow up on a report, the Municipality is the party
                collecting and controlling your personal information for the purpose of running that
                service.
            </p>
            <p>
                This policy is issued under the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>
                and its implementing rules. Where a term here conflicts with that Act, the Act prevails.
            </p>
        </section>

        <section class="legal-section" id="what-we-collect">
            <h2 data-num="02">Information we collect</h2>
            <p>We only collect what the portal needs to function. The categories are:</p>

            <div class="legal-table-wrap">
                <table class="legal-table">
                    <caption class="sr-only">Categories of personal information collected by the portal</caption>
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">What we hold</th>
                            <th scope="col">When it is collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Account details</th>
                            <td>
                                <strong>Name, email address, mobile number, barangay</strong>
                                Passwords are stored only as a one-way cryptographic hash and are never
                                readable by anyone, including staff.
                            </td>
                            <td>When you register, or edit your profile.</td>
                        </tr>
                        <tr>
                            <th scope="row">Identity verification</th>
                            <td>
                                <strong>Your email address, a one-time verification code stored only in
                                hashed form, the number of attempts, and an expiry timestamp</strong>
                                The code itself is never written to the database in plain text.
                            </td>
                            <td>When you register or change your email address.</td>
                        </tr>
                        <tr>
                            <th scope="row">Complaint content</th>
                            <td>
                                <strong>The text you write, the category you choose, up to five photographs
                                you attach, the location you pin, and the address text you type</strong>
                            </td>
                            <td>When you file a complaint.</td>
                        </tr>
                        <tr>
                            <th scope="row">Location data</th>
                            <td>
                                <strong>Coordinates of the point you dropped on the map</strong> If you
                                attach a photograph taken on a phone, we also read the GPS coordinates
                                embedded in that image, and we tell you when the two disagree before filing.
                            </td>
                            <td>When you file a complaint.</td>
                        </tr>
                        <tr>
                            <th scope="row">Communications</th>
                            <td>
                                <strong>Messages and photographs you send when answering a staff question
                                about your own complaint</strong>
                            </td>
                            <td>When you respond to a request for more information.</td>
                        </tr>
                        <tr>
                            <th scope="row">Account security</th>
                            <td>
                                <strong>Password reset tokens</strong> stored as a one-way hash, and any
                                signed link or one-time code you use to verify an action.
                            </td>
                            <td>When you request a password reset or verification.</td>
                        </tr>
                        <tr>
                            <th scope="row">Technical and audit data</th>
                            <td>
                                <strong>Your IP address, your browser and device description, and the time of
                                each action</strong> Recorded against an audit trail that staff cannot edit
                                or delete.
                            </td>
                            <td>Automatically, whenever you sign in or act on a case.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="legal-callout">
                <p>
                    <strong>Photographs are the most sensitive thing you give us.</strong> A phone camera
                    can record not only where a photograph was taken, but the device you used and the
                    moment you took it. You decide what to attach. If a photograph reveals your location
                    and you did not intend it to, tell the department handling your complaint and it can
                    withhold that image from the public register.
                </p>
            </div>
        </section>

        <section class="legal-section" id="why">
            <h2 data-num="03">Why we use it</h2>
            <p>We process your information only for these purposes:</p>
            <ul>
                <li><strong>To accept and route your complaint</strong> to the municipal department best able to act on it.</li>
                <li><strong>To let you track and continue your own report</strong>, and to show you what has happened at each step.</li>
                <li><strong>To let staff investigate and act on the complaint</strong>, including verifying facts on site.</li>
                <li><strong>To protect your identity</strong> in any report you have made public.</li>
                <li><strong>To publish an aggregate public register</strong> so residents can see how the Municipality is performing.</li>
                <li><strong>To keep an audit trail</strong> so that no action on a case can be quietly altered or removed.</li>
                <li><strong>To secure your account</strong>, including verifying your email address and resetting your password.</li>
            </ul>
            <p>
                We do not sell your information, and we do not use it for advertising or profiling. We
                do not share it with any party for marketing purposes.
            </p>
        </section>

        <section class="legal-section" id="legal-basis">
            <h2 data-num="04">Legal basis for processing</h2>
            <p>
                Under the Data Privacy Act, processing must rest on a lawful basis. This portal relies
                on two:
            </p>
            <ul>
                <li>
                    <strong>Consent</strong> - given when you tick the agreement box on the registration
                    form, and when you choose to file a complaint. You may withdraw consent at any time,
                    and withdrawal stops future processing without affecting what was already lawfully done.
                </li>
                <li>
                    <strong>Compliance with law and legitimate public duty</strong> - processing necessary to
                    discharge the Municipality's functions and to meet its record-keeping obligations as a
                    local government unit. This basis does not depend on your consent, but it does not extend
                    beyond what that duty requires.
                </li>
            </ul>
        </section>

        <section class="legal-section" id="public-register">
            <h2 data-num="05">What the public register shows</h2>
            <p>
                Every complaint you file starts <strong>private</strong>. Only you and the staff handling it
                can see it. Making it public is your decision, and it is reversible.
            </p>
            <p>When you choose to publish, the public register shows:</p>
            <ul>
                <li>Your ticket reference number.</li>
                <li>The register list does not name you at all. The public detail page for a report shows your chosen <strong>display alias</strong>, or the words "Community member" if you did not set one - never your legal name against your account.</li>
                <li>The category, the title and description of the complaint, the handling department, and its current status.</li>
                <li>The <strong>barangay</strong>, not a precise coordinate. The public map aggregates reports to the barangay level and shows a count, so no individual pin is exposed.</li>
                <li>The date filed, and how the department settled the matter - for example whether the site was attended or the report was reviewed on paper.</li>
            </ul>
            <p>
                <strong>Staff can moderate what leaves your record.</strong> A department may redact the
                title or description, withhold an image, or remove the report from the public view. Every
                such decision must carry a written reason, and the reason is recorded in the audit trail
                along with who made it and when. Moderation can withhold content from the public; it cannot
                alter your own copy.
            </p>
        </section>

        <section class="legal-section" id="who-can-see">
            <h2 data-num="06">Who can see your information</h2>
            <p>Access is limited by role. Broadly:</p>
            <ul>
                <li><strong>You</strong> see your own complaints, their status, and all updates on them.</li>
                <li><strong>Assigned department staff</strong> see the complaints routed to their department, including your contact details, so they can reach you.</li>
                <li><strong>System administrators</strong> can see account records and act on cases, and their actions are logged.</li>
                <li><strong>Any visitor</strong> sees only what you have chosen to publish, in the form described above.</li>
            </ul>
            <p>
                Access is logged. The audit trail records who did what, to which record, when, and from
                where. Those records are append-only: staff cannot edit or delete them through the system.
            </p>
        </section>

        <section class="legal-section" id="cookies">
            <h2 data-num="07">Cookies and sessions</h2>
            <p>
                The portal uses a session cookie to keep you signed in and to protect forms against
                cross-site request forgery. There is no advertising, tracking, or third-party analytics
                cookie. Your session is stored server-side; the cookie only carries an identifier.
            </p>
            <p>
                A second cookie remembers your language choice (English or Filipino) for a year, so
                you are not asked again on every visit. It carries the language and nothing else, and
                clearing it only means choosing the language again.
            </p>
            <p>
                We do not ask for, set, or read advertising or analytics cookies, and we do not run
                third-party tracking of any kind. Fonts, scripts and styles are served by this
                portal itself, so visiting a page does not send your IP address to an outside
                company.
            </p>
            <p>
                Signing out ends the session. Because your session is server-side, clearing your browser
                cookies without signing out first will not by itself protect an unattended device -
                sign out on shared machines.
            </p>
        </section>

        <section class="legal-section" id="retention">
            <h2 data-num="08">How long we keep it</h2>
            <p>
                We do not publish a fixed retention schedule, and we will not invent one here.
                Information is retained for as long as the law requires the Municipality to keep it in
                order to discharge its functions, meet its record-keeping and audit obligations, answer
                legitimate queries, and preserve the integrity of complaints handling records.
            </p>
            <p>
                In practical terms: complaint records and their audit trail are expected to be retained
                for the full period the Municipality is obliged to keep public service records.
                Expired verification codes and password reset tokens are short-lived by design -
                a verification code expires within minutes, and a reset link is valid only for a limited
                window and can only be used once.
            </p>
            <div class="legal-callout">
                <p>
                    <strong>If you request deletion, we will consider it.</strong> Where deletion would
                    conflict with a legal retention duty or with the integrity of an ongoing case, we
                    will explain that clearly rather than silently ignoring the request. Ask us and we
                    will tell you which category your data falls into and what applies to it.
                </p>
            </div>
        </section>

        <section class="legal-section" id="security">
            <h2 data-num="09">How we protect it</h2>
            <p>
                The portal applies administrative, technical, and physical safeguards appropriate to a
                public service system:
            </p>
            <ul>
                <li>Passwords and verification codes are stored as one-way cryptographic hashes, never in plain text.</li>
                <li>Access is restricted by role and every sensitive action is recorded in an append-only audit trail.</li>
                <li>All pages are served over encrypted connections.</li>
                <li>Uploads are restricted by file type and size, and are served to authorised users rather than placed directly on a public path.</li>
                <li>Forms are protected against cross-site request forgery.</li>
                <li>Email verification is required before an account can be used, so a complaint cannot be filed from an address that does not belong to the person who filed it.</li>
            </ul>
            <p>
                No system is perfectly secure. If a breach affects your personal information, the
                Municipality will notify you and the National Privacy Commission as the law requires.
            </p>
        </section>

        <section class="legal-section" id="your-rights">
            <h2 data-num="10">Your rights</h2>
            <p>Under the Data Privacy Act you have the right to:</p>
            <ul>
                <li><strong>Be informed</strong> - this policy is that notice.</li>
                <li><strong>Access your information</strong> - see the complaints, updates, and account details the portal holds about you.</li>
                <li><strong>Correct it</strong> - change your name, mobile number, barangay, or email address from your profile at any time.</li>
                <li><strong>Object to processing</strong>, and <strong>withdraw consent</strong> you previously gave.</li>
                <li><strong>Be compensated for damage</strong> resulting from a wrongful or negligent act, where the legal requirements are met.</li>
                <li><strong>Complain to the National Privacy Commission</strong> if you believe your information has been mishandled.</li>
            </ul>
            <p>
                Your public alias and your choice to publish are under your control at all times, and
                withdrawing them does not remove the complaint from your own account or from the
                department's working record.
            </p>
        </section>

        <section class="legal-section" id="changes">
            <h2 data-num="11">Changes to this policy</h2>
            <p>
                This policy may be updated as the portal develops or as the Municipality's obligations
                change. The review date at the top of this page always reflects the current version.
                Material changes will be announced through the portal.
            </p>
            <p>
                Continuing to use the portal after a change means the updated policy applies from that
                point. We will not retroactively apply a new term to information already collected.
            </p>
        </section>

        <section class="legal-section" id="contact">
            <h2 data-num="12">How to reach us</h2>
            <p>
                Questions about this policy, or a request concerning your personal information, should be
                directed to the Municipality of Daet, Camarines Norte, through the office that handled
                your complaint or through your account settings.
            </p>
            <p>
                If you are not satisfied with how your concern was handled, you may escalate to the
                <strong>National Privacy Commission</strong>, whose contact details are published by the
                Commission itself. The Municipality will cooperate with that process.
            </p>
            <p>
                You may also read our <a href="{{ route('terms') }}">Terms and Conditions</a>, which
                govern your use of this portal.
            </p>
        </section>

        <div class="legal-cta">
            <h2>Ready to file a complaint?</h2>
            <p>Everything you need to know about how your report is handled is in the Terms and Conditions.</p>
            <div class="legal-cta-actions">
                <a href="{{ route('terms') }}" class="legal-btn legal-btn--ghost">Read the Terms</a>
                <a href="{{ route('complaints.track') }}" class="legal-btn legal-btn--primary" wire:navigate>Track a Report</a>
            </div>
        </div>
    </div>
</div>
@endsection