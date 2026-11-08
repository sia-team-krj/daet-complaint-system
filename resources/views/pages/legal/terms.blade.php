@extends($mainLayout)

@section('title', 'Terms and Conditions - Daet Listens')

{{--
    Terms and Conditions for the Daet Listens complaint portal.

    Written against the behaviour this codebase actually implements. The clauses
    below correspond to real mechanisms: complaints carry a `DLT-` ticket
    reference and are tracked by that reference alone; the resident chooses
    public or private visibility and may change it; staff moderation requires a
    written reason and is recorded in the audit trail; spam and near-duplicate
    detection can flag a report; and information requests create a two-way
    exchange between the resident and the department.

    Deliberately not promised: any service level or response time. The code has
    no SLA mechanism, and a commitment the system cannot measure becomes a lie
    the first time a queue backs up.
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
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
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
            font-family: "Cormorant Garamond", Georgia, serif;
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
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .legal-section > h2::before {
            content: attr(data-num);
            color: var(--lg-gold);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
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

        .legal-callout--warning {
            background: rgba(180, 83, 9, 0.06);
            border-color: rgba(180, 83, 9, 0.3);
            border-left-color: #b45309;
        }

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
            font-family: "Cormorant Garamond", Georgia, serif;
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
            <h1 class="legal-title">Terms and Conditions</h1>
            <p class="legal-lede">
                These terms govern your use of the Daet Listens complaint portal. By registering, filing
                a complaint, or tracking a report, you agree to them. They set out what the portal will
                do, what it will not promise, and what you agree to do in return.
            </p>
            <p class="legal-updated">Last reviewed: {{ now()->format('F j, Y') }}</p>
        </div>
    </header>

    <div class="legal-body">
        <nav class="legal-toc" aria-label="Terms and Conditions contents">
            <p class="legal-toc-title">On this page</p>
            <ol>
                <li><a href="#acceptance">Acceptance of these terms</a></li>
                <li><a href="#what-the-portal-is">What this portal is</a></li>
                <li><a href="#accounts">Your account</a></li>
                <li><a href="#filing">Filing a complaint</a></li>
                <li><a href="#evidence">Photographs and evidence</a></li>
                <li><a href="#tracking">Tracking and ticket numbers</a></li>
                <li><a href="#visibility">Public and private reports</a></li>
                <li><a href="#acceptable-use">Acceptable use</a></li>
                <li><a href="#moderation">Moderation and suspension</a></li>
                <li><a href="#no-guarantee">No guarantee of outcome</a></li>
                <li><a href="#liability">Limitation of liability</a></li>
                <li><a href="#changes">Changes to these terms</a></li>
                <li><a href="#governing-law">Governing law</a></li>
            </ol>
        </nav>

        <section class="legal-section" id="acceptance">
            <h2 data-num="01">Acceptance of these terms</h2>
            <p>
                These terms apply to everyone who uses the Daet Listens complaint portal, including
                visitors who only browse the public transparency register. By using the portal you accept
                them. If you do not accept them, do not use the portal.
            </p>
            <p>
                These terms sit alongside our <a href="{{ route('privacy') }}">Privacy Policy</a>, which
                explains how your personal information is handled. The Privacy Policy forms part of
                these terms.
            </p>
        </section>

        <section class="legal-section" id="what-the-portal-is">
            <h2 data-num="02">What this portal is</h2>
            <p>
                Daet Listens is a <strong>reporting and transparency channel</strong> between residents
                and the Municipality of Daet, Camarines Norte. It lets you:
            </p>
            <ul>
                <li>File a complaint and attach evidence.</li>
                <li>Follow what happens to it, step by step, as each department acts.</li>
                <li>Answer a question from a department about your own complaint.</li>
                <li>Decide whether your report is public, and change that decision later.</li>
                <li>See an aggregate public register of what other residents have reported.</li>
            </ul>
            <p>
                It is <strong>not</strong> an emergency channel. If there is an immediate danger to
                life or property, contact the appropriate emergency services directly rather than
                filing a complaint here.
            </p>
        </section>

        <section class="legal-section" id="accounts">
            <h2 data-num="03">Your account</h2>
            <h3>Registration</h3>
            <p>
                You must register before filing a complaint. Registration requires your name, a valid
                email address, and a password of at least eight characters containing upper and lower
                case letters and a number. A mobile number and barangay are optional but help a
                department reach you.
            </p>
            <h3>Email verification</h3>
            <p>
                New accounts must verify their email address before use. Verification is a one-time
                code sent to the address you registered, and it expires. If you cannot receive it, do
                not create repeated accounts - request a resend or contact the Municipality.
            </p>
            <h3>Your responsibilities</h3>
            <ul>
                <li>Give accurate information and keep it current. You can change your details from your profile.</li>
                <li>Keep your password to yourself. You are responsible for what happens under your account.</li>
                <li>Tell us promptly if you suspect someone else has used your account.</li>
            </ul>
            <p>
                Accounts are for residents and authorised municipal staff. An account may be suspended
                where it is used to file abuse, to impersonate someone, or to breach these terms.
            </p>
        </section>

        <section class="legal-section" id="filing">
            <h2 data-num="04">Filing a complaint</h2>
            <p>
                You may file a complaint about a public service, facility, or matter within the
                Municipality's responsibility. To help a department act, describe what happened, when,
                where, and what outcome you are seeking.
            </p>
            <p>When you file, you confirm that:</p>
            <ul>
                <li>The information you give is truthful and accurate to the best of your knowledge.</li>
                <li>You have the right to raise the matter, and you are not filing it on someone else's behalf without authority.</li>
                <li>You have not already filed substantially the same complaint on this portal.</li>
                <li>You will not upload material you have no right to share.</li>
            </ul>
            <p>
                Complaints are routed automatically to the municipal department best able to act. You
                can follow the routing on your dashboard. Nothing you file guarantees any particular
                outcome - see the section on guarantees below.
            </p>
        </section>

        <section class="legal-section" id="evidence">
            <h2 data-num="05">Photographs and evidence</h2>
            <p>
                You may attach up to five images as evidence, in JPEG, PNG, or WebP format, up to
                5&nbsp;MB each. Staff may also attach documentation as they process your case.
            </p>
            <div class="legal-callout legal-callout--warning">
                <p>
                    <strong>Before you upload a photograph, check what it reveals.</strong> Phone
                    cameras commonly embed GPS coordinates, and photographs of people, vehicles,
                    number plates, or private premises can identify individuals. Once attached, an
                    image may be seen by the handling department even if you later make the complaint
                    private. If an image captures something you did not intend to disclose, tell the
                    department handling your complaint.
                </p>
            </div>
            <p>
                Photographs you upload are stored privately. They are not published unless you make the
                complaint public and staff do not withhold that specific image. Staff may withhold an
                image from the public view for a recorded reason, but your own copy is unaffected.
            </p>
        </section>

        <section class="legal-section" id="tracking">
            <h2 data-num="06">Tracking and ticket numbers</h2>
            <p>
                Every complaint is issued a unique reference beginning with <span class="legal-field">DLT-</span>.
                Keep it: it is what lets you track a report without an account, and it is what the
                Municipality will ask for if you follow up by phone or in person.
            </p>
            <p>
                Only you and the staff handling the complaint can see it. Treat the ticket number like
                a password - anyone with it can look up the status of the report.
            </p>
        </section>

        <section class="legal-section" id="visibility">
            <h2 data-num="07">Public and private reports</h2>
            <p>
                Every complaint starts <strong>private</strong>. It is visible only to you and to the
                staff handling it. You decide whether to publish it, and you can change that decision
                at any time from your account.
            </p>
            <p>If you choose to publish, your report appears in the public transparency register where:</p>
            <ul>
                <li>The register list never names you. The public detail page for a report shows your display alias, or "Community member" - never your legal name.</li>
                <li>Your location is shown at barangay level and aggregated into a count on the map, never as a precise coordinate.</li>
                <li>The public sees the content of your report and how the department settled it.</li>
            </ul>
            <p>
                Publishing is a decision about transparency, not a waiver of your rights. Making a
                report private again removes it from the public register going forward.
            </p>
        </section>

        <section class="legal-section" id="acceptable-use">
            <h2 data-num="08">Acceptable use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>File false, misleading, or vexatious complaints, including any you know to be untrue.</li>
                <li>Impersonate another person, or file on behalf of someone else without their authority.</li>
                <li>Harass, threaten, defame, or abuse any resident, staff member, or member of the public.</li>
                <li>Upload malware, or material that is unlawful, obscene, discriminatory, or infringes someone else's rights.</li>
                <li>Upload the personal information of a third party without a lawful basis for doing so.</li>
                <li>Attempt to access accounts, records, or functions you are not authorised to use, including through automated requests.</li>
                <li>Scrape, overload, or otherwise interfere with the portal's operation.</li>
                <li>Misuse staff or resident accounts issued to municipal employees.</li>
            </ul>
            <p>
                The portal applies automated checks that look for spam, bulk submission, and reports
                substantially identical to existing ones. A report may be flagged for staff review.
                Flagging is not a rejection: it is a hold for a human to look at it.
            </p>
        </section>

        <section class="legal-section" id="moderation">
            <h2 data-num="09">Moderation and suspension</h2>
            <p>
                Staff may moderate content on a public report - redacting the title or description,
                withholding an image, or removing the report from the public view - where publishing it
                would breach a person's privacy, expose them to harm, disclose information the law
                protects, or defeat the purpose of the register.
            </p>
            <p>Every moderation decision must:</p>
            <ul>
                <li>Carry a written reason.</li>
                <li>Record who made it and when, in an audit trail staff cannot edit or delete.</li>
                <li>Leave your own copy of the complaint unchanged.</li>
            </ul>
            <p>
                Moderation is limited to what leaves your record. Staff can withhold content from the
                public view; they cannot rewrite your account or your own copy of what you filed. If you
                disagree with a moderation decision, ask for it to be reviewed and you will be told the
                reason.
            </p>
            <p>
                Breach of the acceptable use terms may result in a warning, suspension of your account,
                or removal of content. Where a report is removed, you will be told why.
            </p>
        </section>

        <section class="legal-section" id="no-guarantee">
            <h2 data-num="10">No guarantee of outcome</h2>
            <p>
                The portal records your complaint and routes it. It does not decide the outcome. We do
                not promise that a complaint will be upheld, that a specific department will act within
                any period, or that a remedy will be granted. Filings are handled according to the
                Municipality's capacity, priority, and applicable law.
            </p>
            <p>
                Nothing in these terms limits the Municipality's powers or duties under applicable
                law, or the right of any person to pursue a complaint or appeal outside this portal
                through the proper channel.
            </p>
        </section>

        <section class="legal-section" id="liability">
            <h2 data-num="11">Limitation of liability</h2>
            <p>
                To the extent permitted by law, the Municipality of Daet is not liable for indirect or
                consequential loss arising from your use of the portal, including reliance on the
                availability of the service or on information displayed in the public register.
            </p>
            <p>
                The portal is provided as it stands. We take reasonable steps to keep it available and to
                protect your information, but we do not warrant that it will be uninterrupted or free of
                error. Nothing in these terms excludes liability that cannot lawfully be excluded,
                including liability for fraud.
            </p>
        </section>

        <section class="legal-section" id="changes">
            <h2 data-num="12">Changes to these terms</h2>
            <p>
                These terms may be updated as the portal develops or the Municipality's obligations
                change. The review date at the top of this page always reflects the current version.
                Material changes will be announced through the portal.
            </p>
            <p>
                Continuing to use the portal after a change means the updated terms apply from that
                point. New terms are never applied retroactively to complaints already filed.
            </p>
        </section>

        <section class="legal-section" id="governing-law">
            <h2 data-num="13">Governing law</h2>
            <p>
                These terms are governed by and construed in accordance with the laws of the Republic
                of the Philippines, and subject to the exclusive jurisdiction of the proper Philippine
                courts.
            </p>
            <p>In particular, and without limiting the above:</p>
            <ul>
                <li>
                    <strong>The Data Privacy Act of 2012 (Republic Act No. 10173)</strong> governs the
                    processing of your personal information, as set out in our
                    <a href="{{ route('privacy') }}">Privacy Policy</a>.
                </li>
                <li>
                    <strong>Republic Act No. 11032</strong>, the Anti-Junk Email Act, applies to
                    electronic messages sent through this service. The portal sends you messages about
                    your account and your complaints - verification codes, password resets, status
                    updates - because you asked for the service and those messages are necessary to
                    deliver it. We do not send marketing email.
                </li>
                <li>Local ordinances of the Municipality of Daet and applicable national legislation govern how complaints are received, processed, and retained.</li>
            </ul>
        </section>

        <div class="legal-cta">
            <h2>Ready to file a complaint?</h2>
            <p>Read the Privacy Policy first so you know exactly what happens to what you submit.</p>
            <div class="legal-cta-actions">
                <a href="{{ route('privacy') }}" class="legal-btn legal-btn--ghost">Read the Privacy Policy</a>
                <a href="{{ route('complaints.create') }}" class="legal-btn legal-btn--primary" wire:navigate>File a Complaint</a>
            </div>
        </div>
    </div>
</div>
@endsection