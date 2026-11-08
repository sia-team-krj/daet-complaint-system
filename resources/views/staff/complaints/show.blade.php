@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Staff View')


@section('staff-content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  :root {
    --navy: #142D4F;
    --navy-mid: #1B3A61;
    --gold: #C9A84C;
    --gold-light: #E2C06A;
    --gold-pale: rgba(201,168,76,0.12);
    --white: #ffffff;
    --text-dim: rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
    --red: #EF4444;
    --amber: #F59E0B;
    --green: #3FCB6F;
    --blue: #3B82F6;
  }

  .staff-root {
    min-height: calc(100svh - var(--navbar-height));
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    position: relative;
    padding: 40px 0;
  }

  .decorations {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
  }
  .stripe-bg {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px);
  }
  .gold-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  .glow-tr {
    position: absolute;
    top: -10%;
    right: -5%;
    width: 40vw;
    height: 40vw;
    max-width: 500px;
    max-height: 500px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
  }
  .glow-bl {
    position: absolute;
    bottom: -10%;
    left: -5%;
    width: 35vw;
    height: 35vw;
    max-width: 400px;
    max-height: 400px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 68%);
  }
  .watermark {
    position: absolute;
    bottom: 2%;
    right: 3%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 15vw, 200px);
    font-weight: 700;
    color: rgba(255,255,255,0.018);
    line-height: 1;
    user-select: none;
  }

  .staff-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    position: relative;
    z-index: 5;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fu { animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1) both; }
  .d1 { animation-delay: 0.04s; }
  .d2 { animation-delay: 0.14s; }
  .d3 { animation-delay: 0.24s; }
  .d4 { animation-delay: 0.34s; }
  .d5 { animation-delay: 0.44s; }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
  }
  .breadcrumb a {
    color: var(--text-dim);
    text-decoration: none;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .breadcrumb a:hover { color: var(--gold); }
  .breadcrumb-gold { color: var(--gold); }
  .breadcrumb-current {
    font-family: 'Cormorant Garamond', serif;
    color: var(--gold);
    font-weight: 700;
    font-size: 14px;
  }
  .breadcrumb-sep { color: rgba(255,255,255,0.25); }

  .page-header {
    margin-bottom: 32px;
  }
  .ticket-display {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 5vw, 48px);
    font-weight: 700;
    color: var(--gold);
    margin-bottom: 12px;
  }
  .complaint-title-display {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(20px, 3vw, 28px);
    font-weight: 600;
    color: var(--white);
    margin-bottom: 16px;
  }

  .status-badge-lg {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 16px;
    border: 1px solid;
  }
  .status-submitted {
    background: rgba(59,130,246,0.12);
    border-color: rgba(59,130,246,0.3);
    color: var(--blue);
  }
  .status-in-progress {
    background: rgba(245,158,11,0.12);
    border-color: rgba(245,158,11,0.3);
    color: var(--amber);
  }
  .status-resolved {
    background: rgba(63,203,111,0.12);
    border-color: rgba(63,203,111,0.3);
    color: var(--green);
  }
  .status-rejected {
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.3);
    color: var(--red);
  }

  .meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    font-size: 13px;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
  }
  .meta-item { display: flex; align-items: center; gap: 6px; }
  .meta-dot { color: rgba(255,255,255,0.25); }
  .meta-value { color: rgba(255,255,255,0.8); }

  .detail-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 28px;
  }

  .card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    margin-bottom: 24px;
  }
  .card:last-child { margin-bottom: 0; }
  .card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
  }
  .card-header-icon {
    width: 36px;
    height: 36px;
    background: var(--gold-pale);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
  }
  .card-header-icon svg { width: 18px; height: 18px; }
  .card-header-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 17px;
    font-weight: 700;
    color: var(--white);
  }
  .card-body { padding: 24px; }

  .citizen-row {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .citizen-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--navy-mid);
    border: 1px solid var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 15px;
    font-weight: 700;
    color: var(--gold);
    flex-shrink: 0;
  }
  .citizen-info h4 {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 500;
    color: var(--white);
    margin-bottom: 4px;
  }
  .citizen-info p {
    font-size: 13px;
    color: var(--text-dim);
    margin: 0;
  }

  .review-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
  }
  .review-summary > div {
    padding: 11px 12px;
    border: 1px solid rgba(201,168,76,0.16);
    border-radius: 4px;
    background: rgba(255,255,255,0.03);
  }
  .review-summary__label {
    display: block;
    margin-bottom: 6px;
    color: var(--text-dim);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }
  .review-summary strong {
    color: var(--white);
    font-size: 12px;
  }
  .review-badge {
    display: inline-flex;
    padding: 4px 7px;
    border-radius: 3px;
    font-size: 10px;
  }
  .review-pending { color: #fcd34d; background: rgba(245,158,11,0.12); }
  .review-verified { color: #86efac; background: rgba(63,203,111,0.12); }
  .review-needs-information { color: #fcd34d; background: rgba(245,158,11,0.12); }
  .review-duplicate { color: #c4b5fd; background: rgba(139,92,246,0.12); }
  .review-rejected { color: #fca5a5; background: rgba(239,68,68,0.12); }
  .review-escalated { color: #fca5a5; background: rgba(239,68,68,0.12); }
  .review-reasons {
    margin-bottom: 18px;
    padding: 12px 14px;
    border-left: 2px solid var(--gold);
    background: rgba(255,255,255,0.03);
    color: var(--text-dim);
    font-size: 11px;
    line-height: 1.55;
  }
  .review-reasons strong { color: var(--white); }
  .review-reasons ul { margin: 7px 0 0 17px; }
  .review-form { padding-top: 4px; border-top: 1px solid rgba(201,168,76,0.14); }
  .review-public-toggle {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 2px 0 18px;
    color: var(--text-dim);
    font-size: 11px;
    line-height: 1.5;
  }
  .review-public-toggle input { accent-color: var(--gold); margin-top: 2px; }

  .description-text {
    font-size: 14px;
    line-height: 1.85;
    color: rgba(255,255,255,0.8);
    margin-bottom: 16px;
  }
  .category-chip {
    display: inline-block;
    padding: 6px 14px;
    background: var(--gold-pale);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--gold);
  }

  .map-container {
    height: 280px;
    border-radius: 0 0 6px 6px;
    overflow: hidden;
  }
  /* A pin on a map is not something you can type into a handheld GPS, and
     reaching the site without signal is when the numbers matter most. */
  .complaint-coords {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 8px;
    padding: 12px 18px;
    border-top: 1px solid var(--border);
    color: var(--text-dim);
    font-size: 13px;
  }
  .complaint-coords__value {
    color: var(--text);
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.01em;
  }
  .map-placeholder {
    height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.03);
    color: var(--text-dim);
    gap: 12px;
  }
  .map-placeholder svg {
    width: 40px;
    height: 40px;
    opacity: 0.3;
    color: var(--gold);
  }

  .gold-marker {
    width: 14px;
    height: 14px;
    background: var(--gold);
    border-radius: 50%;
    border: 2px solid var(--white);
    position: relative;
  }
  .gold-marker::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid var(--gold);
    animation: pulse 2s ease-out infinite;
    opacity: 0;
  }
  @keyframes pulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(2.5); opacity: 0; }
  }

  .photo-wrap {
    border: 1px solid var(--border-gold);
    border-radius: 4px;
    overflow: hidden;
  }
  .photo-wrap img {
    width: 100%;
    display: block;
  }
  .photo-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
  }
  .photo-gallery .photo-wrap img {
    aspect-ratio: 1;
    object-fit: cover;
  }

  .documentation-field {
    padding: 15px;
    background: rgba(201, 168, 76, 0.055);
    border: 1px solid rgba(201, 168, 76, 0.24);
    border-radius: 5px;
  }
  .documentation-field .form-label {
    color: var(--gold-light);
  }
  .documentation-field .form-hint {
    margin: 0 0 12px;
  }
  .documentation-sources {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .documentation-source {
    display: grid;
    grid-template-columns: 32px minmax(0, 1fr);
    align-items: start;
    gap: 10px;
    min-width: 0;
    padding: 13px;
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid rgba(201, 168, 76, 0.22);
    border-radius: 5px;
  }
  .documentation-source--camera {
    background: rgba(201, 168, 76, 0.07);
    border-color: rgba(201, 168, 76, 0.42);
  }
  .documentation-source__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: var(--gold-light);
    background: rgba(201, 168, 76, 0.12);
    border: 1px solid rgba(201, 168, 76, 0.28);
    border-radius: 4px;
  }
  .documentation-source__icon svg {
    width: 17px;
    height: 17px;
  }
  .documentation-source__copy {
    display: grid;
    gap: 4px;
    min-width: 0;
  }
  .documentation-source__copy strong {
    color: var(--white);
    font-size: 12px;
    font-weight: 700;
  }
  .documentation-source__copy span {
    color: var(--text-dim);
    font-size: 10px;
    line-height: 1.45;
  }
  .documentation-source__action {
    grid-column: 1 / -1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    margin-top: 3px;
    padding: 6px 10px;
    color: var(--gold-light);
    background: transparent;
    border: 1px solid rgba(201, 168, 76, 0.42);
    border-radius: 4px;
    font: inherit;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.18s, color 0.18s, border-color 0.18s;
  }
  .documentation-source__action:hover,
  .documentation-source__action:focus-visible {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold-light);
  }
  .documentation-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    clip-path: inset(50%);
    white-space: nowrap;
  }
  .documentation-selection {
    display: flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    margin-top: 11px;
    color: var(--text-dim);
    font-size: 10px;
  }
  .documentation-selection strong {
    color: var(--gold-light);
    font-size: 10px;
  }
  .documentation-client-error {
    margin-top: 7px;
    color: #fca5a5;
    font-size: 11px;
    line-height: 1.45;
  }
  .documentation-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
    gap: 8px;
    margin-top: 11px;
  }
  .documentation-previews:empty {
    display: none;
  }
  .documentation-preview {
    position: relative;
    min-width: 0;
    aspect-ratio: 1;
    overflow: hidden;
    border: 1px solid rgba(201, 168, 76, 0.3);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.04);
  }
  .documentation-preview img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .documentation-preview-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 25px;
    height: 25px;
    padding: 0;
    color: #fff;
    background: rgba(11, 31, 58, 0.82);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 50%;
    cursor: pointer;
  }
  .documentation-preview-remove:hover,
  .documentation-preview-remove:focus-visible {
    background: var(--navy);
    border-color: var(--gold-light);
  }
  .documentation-preview-remove svg {
    width: 13px;
    height: 13px;
  }
  .timeline-evidence {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
    gap: 7px;
    margin: 10px 0;
  }
  .timeline-evidence a {
    display: block;
    overflow: hidden;
    border: 1px solid var(--border-gold);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.04);
  }
  .timeline-evidence img {
    display: block;
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
  }

  .form-group { margin-bottom: 20px; }
  .form-group:last-child { margin-bottom: 0; }
  .form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    margin-bottom: 10px;
  }
  .form-label-icon {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    color: currentColor;
  }
  .form-label .indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }
  .indicator-green { background: var(--green); }
  .indicator-amber { background: var(--amber); }
  .form-select, .form-textarea {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 4px;
    padding: 12px 16px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--white);
    outline: none;
    transition: all 0.2s;
  }
  .form-select:focus, .form-textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
  }
  .form-textarea { min-height: 120px; resize: vertical; }
  .form-select option { background: var(--navy); color: var(--white); }

  .form-hint {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 6px;
  }
  .form-error {
    font-size: 12px;
    color: var(--red);
    margin-top: 6px;
  }

  .internal-field .form-textarea {
    border-color: rgba(245,158,11,0.3);
  }
  .internal-field .form-textarea:focus {
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
  }
  .internal-field .form-label {
    color: var(--amber);
  }
  .internal-hint {
    font-size: 12px;
    color: var(--amber);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .btn-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 16px 28px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    border: none;
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: var(--navy);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(201,168,76,0.25);
  }
  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(201,168,76,0.35);
  }

  .right-col {
    position: sticky;
    top: 88px;
    height: fit-content;
  }

  .timeline {
    position: relative;
    padding-left: 24px;
  }
  .timeline::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 4px;
    bottom: 4px;
    width: 2px;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.2));
  }
  .timeline-item {
    position: relative;
    padding-bottom: 24px;
  }
  .timeline-item:last-child { padding-bottom: 0; }
  .timeline-item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--gold);
    border: 2px solid var(--navy);
  }
  .timeline-item.internal::before {
    background: var(--amber);
    border-style: dashed;
  }

  .timeline-avatar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
  }
  .timeline-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gold-pale);
    border: 1px solid var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--gold);
  }
  .timeline-actor-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--white);
  }
  .timeline-role-badge {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 8px;
    background: var(--gold-pale);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 3px;
    color: var(--gold);
  }

  .status-change-pill {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(201,168,76,0.1);
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 30px;
    font-size: 11px;
    color: var(--gold);
    margin-bottom: 8px;
  }

  .timeline-note {
    background: rgba(255,255,255,0.03);
    border-left: 2px solid var(--gold);
    padding: 12px 14px;
    border-radius: 0 4px 4px 0;
    font-size: 13px;
    line-height: 1.6;
    color: rgba(255,255,255,0.85);
    margin-bottom: 8px;
  }
  .timeline-item.internal .timeline-note {
    border-left-style: dashed;
    border-left-color: var(--amber);
    background: rgba(245,158,11,0.05);
  }

  .timeline-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 3px 10px;
    border-radius: 3px;
    margin-right: 8px;
  }
  .timeline-tag svg {
    width: 11px;
    height: 11px;
    flex-shrink: 0;
  }
  .tag-public {
    background: rgba(63,203,111,0.12);
    color: var(--green);
  }
  .tag-internal {
    background: rgba(245,158,11,0.12);
    color: var(--amber);
  }
  .timeline-time {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 4px;
  }

  .timeline-empty {
    text-align: center;
    padding: 40px 20px;
  }
  .timeline-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    opacity: 0.3;
    color: var(--gold);
  }
  .timeline-empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    color: var(--white);
    margin-bottom: 6px;
  }
  .timeline-empty-text {
    font-size: 13px;
    color: var(--text-dim);
  }

  .alert {
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .alert-success {
    background: rgba(63,203,111,0.1);
    border: 1px solid rgba(63,203,111,0.25);
    color: var(--green);
  }
  .alert-error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    color: var(--red);
  }

  @media (max-width: 1100px) {
    .detail-grid { grid-template-columns: 1fr; }
    .right-col { position: static; }
  }
  @media (max-width: 768px) {
    .staff-container { padding: 0 24px; }
    .ticket-display { font-size: 32px; }
    .map-container, .map-placeholder { height: 220px; }
    .breadcrumb { flex-wrap: wrap; }
    .review-summary { grid-template-columns: 1fr; }
  }
  @media (max-width: 600px) {
    .documentation-sources { grid-template-columns: 1fr; }
  }
</style>

<div class="staff-root">
  <div class="decorations">
    <div class="stripe-bg"></div>
    <div class="gold-bar"></div>
    <div class="glow-tr"></div>
    <div class="glow-bl"></div>
    <div class="watermark">DAET</div>
  </div>

  <div class="staff-container">
    {{-- Breadcrumb --}}
    <nav class="breadcrumb fu d1">
      <a href="{{ route('staff.dashboard') }}">
        <span style="color: var(--gold);">←</span>
        <span>Staff Dashboard</span>
      </a>
      <span class="breadcrumb-sep">·</span>
      <span class="breadcrumb-gold">{{ $complaint->department->name ?? 'Department' }}</span>
      <span class="breadcrumb-sep">·</span>
      <span class="breadcrumb-current">{{ $complaint->ticket_id }}</span>
    </nav>

    {{-- Flash Messages --}}
    @if(session('status'))
      <div class="alert alert-success fu d2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        {{ session('status') }}
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-error fu d2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
      </div>
    @endif

    {{-- Page Header --}}
    @php
      $statusClass = match($complaint->statusEnum->value) {
        'Submitted' => 'status-submitted',
        'In Progress' => 'status-in-progress',
        'Resolved' => 'status-resolved',
        'Rejected' => 'status-rejected',
        default => 'status-submitted'
      };
      $daysOpen = $complaint->created_at->diffInDays(now());
    @endphp

    <header class="page-header fu d2">
      <div class="ticket-display">{{ $complaint->ticket_id }}</div>
      <h1 class="complaint-title-display">{{ $complaint->title }}</h1>
      <span class="status-badge-lg {{ $statusClass }}">{{ $complaint->statusEnum->label() }}</span>
      <div class="meta-row">
        <span class="meta-item">Filed on <span class="meta-value">{{ $complaint->created_at->format('F d, Y') }}</span></span>
        <span class="meta-dot">·</span>
        <span class="meta-item">{{ $complaint->category }}</span>
        <span class="meta-dot">·</span>
        <span class="meta-item">@if($complaint->department)<span class="dept-indicator" data-dept="{{ $complaint->department->code }}"><span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span><span>{{ $complaint->department->name }}</span></span>@else Unassigned @endif</span>
        <span class="meta-dot">·</span>
        <span class="meta-item">{{ $daysOpen }} days open</span>
      </div>
    </header>

    {{-- Two Column Grid --}}
    <div class="detail-grid">
      {{-- Left Column --}}
      <div class="fu d3">
        {{-- Citizen Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="card-header-title">Resident information</div>
          </div>
          <div class="card-body">
            <div class="citizen-row">
              @php
                $residentAlias = $complaint->user?->staff_safe_name ?? 'Resident unavailable';
              @endphp
              <div class="citizen-avatar">{{ strtoupper(substr($residentAlias, 0, 1)) }}</div>
              <div class="citizen-info">
                <h4>{{ $residentAlias }}</h4>
                <p>Identity hidden from staff · admin-only access</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Review & Priority Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"></path></svg>
            </div>
            <div class="card-header-title">Review and priority</div>
          </div>
          <div class="card-body">
            <div class="review-summary">
              <div>
                <span class="review-summary__label">Review status</span>
                <strong class="review-badge {{ $complaint->reviewStatusEnum->badgeClass() }}">{{ $complaint->reviewStatusEnum->label() }}</strong>
              </div>
              <div>
                <span class="review-summary__label">Suggested priority</span>
                <strong>{{ $complaint->suggestedPriorityEnum->label() }}</strong>
              </div>
              <div>
                <span class="review-summary__label">Confirmed priority</span>
                <strong>{{ $complaint->confirmedPriorityEnum?->label() ?? 'Not confirmed' }}</strong>
              </div>
            </div>

            @if($complaint->suggestion_reasons)
              <div class="review-reasons">
                <strong>Why the system suggested this:</strong>
                <ul>
                  @foreach($complaint->suggestion_reasons as $reason)
                    <li>{{ $reason }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('staff.complaints.review', $complaint) }}" class="review-form">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label class="form-label" for="review_status">Review decision</label>
                <select name="review_status" id="review_status" class="form-select" required>
                  @foreach($reviewStatusOptions as $reviewOption)
                    <option value="{{ $reviewOption->value }}" @selected($complaint->review_status === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="confirmed_priority">Confirmed priority</label>
                <select name="confirmed_priority" id="confirmed_priority" class="form-select">
                  <option value="">Not confirmed</option>
                  @foreach($priorityOptions as $priorityOption)
                    <option value="{{ $priorityOption->value }}" @selected($complaint->confirmed_priority === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                  @endforeach
                </select>
                <div class="form-hint">Required when the review decision is Verified.</div>
              </div>
              <div class="form-group">
                <label class="form-label" for="verification_method">How was this checked</label>
                <select name="verification_method" id="verification_method" class="form-select">
                  <option value="">Not recorded</option>
                  @foreach(\App\Enums\VerificationMethod::cases() as $methodOption)
                    <option value="{{ $methodOption->value }}" @selected($complaint->verification_method === $methodOption->value)>{{ $methodOption->label() }}</option>
                  @endforeach
                </select>
                <div class="form-hint">This is what the public register shows. "Report reviewed" and "Site attended" are different claims.</div>
              </div>
              <div class="form-group">
                <label class="form-label" for="review_notes">Review notes</label>
                <textarea name="review_notes" id="review_notes" class="form-textarea" rows="3" placeholder="Record what was checked and why.">{{ old('review_notes', $complaint->review_notes) }}</textarea>
              </div>
              <button type="submit" class="btn-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                Save review decision
              </button>
            </form>

            {{-- Verification is a capability, so these only render for staff who
                 actually hold it. Everything above stays visible to every staff
                 member; only the decision is gated. --}}
            @if(auth()->user()->canVerifyComplaints())
              <div class="verification-extra">
                <h3 class="verification-extra__title">Need more before deciding?</h3>

                @php $outstandingRequest = $complaint->outstandingInformationRequest(); @endphp

                @if($outstandingRequest)
                  <div class="verification-request verification-request--{{ $outstandingRequest->status->value }}">
                    <p class="verification-request__status">{{ $outstandingRequest->status->label() }} · round {{ $outstandingRequest->round }}</p>
                    <p class="verification-request__question">{{ $outstandingRequest->question }}</p>
                    @if($outstandingRequest->response_text)
                      <p class="verification-request__response">
                        <strong>Resident replied:</strong> {{ $outstandingRequest->response_text }}
                      </p>
                    @endif
                  </div>
                @else
                  <form method="POST" action="{{ route('staff.complaints.verification.request-information', $complaint) }}" class="verification-extra__form">
                    @csrf
                    <div class="form-group">
                      <label class="form-label" for="question">What is missing?</label>
                      <textarea name="question" id="question" class="form-textarea" rows="3" required minlength="15" maxlength="1000" placeholder="The streetlight photo is too dark to read the pole number. Please send a closer shot.">{{ old('question') }}</textarea>
                      <div class="form-hint">The resident sees this question, so be specific about what would settle it.</div>
                      @error('question')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn-submit btn-submit--ghost">Ask the resident for more evidence</button>
                  </form>
                @endif

                @if($departmentVerifiers->isNotEmpty())
                  <form method="POST" action="{{ route('staff.complaints.verification.delegate', $complaint) }}" class="verification-extra__form">
                    @csrf
                    <div class="form-group">
                      <label class="form-label" for="verifying_staff_id">Hand verification to</label>
                      <select name="verifying_staff_id" id="verifying_staff_id" class="form-select">
                        @foreach($departmentVerifiers as $verifierOption)
                          <option value="{{ $verifierOption->id }}" @selected($complaint->verifying_staff_id === $verifierOption->id)>
                            {{ $verifierOption->full_name }}{{ $verifierOption->id === $complaint->assigned_staff_id ? ' (assigned worker)' : '' }}
                          </option>
                        @endforeach
                      </select>
                      <div class="form-hint">The assigned worker cannot verify their own assignment.</div>
                      @error('verifying_staff_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn-submit btn-submit--ghost">Hand over verification</button>
                  </form>
                @endif
              </div>
            @endif
          </div>
        </div>

        {{-- Description Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="card-header-title">Complaint Description</div>
          </div>
          <div class="card-body">
            <div class="description-text">{{ $complaint->description }}</div>
            <span class="category-chip">{{ $complaint->category }}</span>
          </div>
        </div>

        {{-- Location Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="card-header-title">Complaint Location</div>
          </div>
          @if($complaint->latitude !== null && $complaint->longitude !== null)
            <div id="complaint-map" class="map-container"
                 data-latitude="{{ (float) $complaint->latitude }}"
                 data-longitude="{{ (float) $complaint->longitude }}"></div>
            <div class="complaint-coords">
              <span class="complaint-coords__value">{{ number_format((float) $complaint->latitude, 6) }}, {{ number_format((float) $complaint->longitude, 6) }}</span>
              @if($complaint->user?->barangay)
                <span>{{ $complaint->user->barangay }}</span>
              @endif
            </div>
          @else
            <div class="map-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>No location provided</span>
            </div>
          @endif
        </div>

        {{-- Photo Card (conditional) --}}
        @if($complaint->evidence_images)
          <div class="card">
            <div class="card-header">
              <div class="card-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
              <div class="card-header-title">Attached Photos @if($complaint->evidence_image_count > 1)<small>({{ $complaint->evidence_image_count }})</small>@endif</div>
            </div>
            <div class="card-body">
              <div class="photo-gallery">
                @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                  <a href="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" class="photo-wrap">
                    <img src="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" alt="Complaint evidence photo {{ $imageIndex + 1 }}" loading="lazy">
                  </a>
                @endforeach
              </div>
            </div>
          </div>
        @endif
      </div>

      {{-- Right Column (Sticky) --}}
      <div class="right-col fu d4">
        {{-- Response Form Card --}}
        @if($complaint->isVerified() && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Rejected && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Closed)
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div class="card-header-title">Add Response</div>
          </div>
          <div class="card-body">
            <form id="staff-response-form" method="POST" action="{{ route('staff.complaints.update', $complaint) }}" enctype="multipart/form-data">
              @csrf
              @method('PUT')

              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="staff-status-select" class="form-select" required>
                  @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" {{ $complaint->statusEnum->value === $statusOption->value ? 'selected' : '' }}>
                      {{ $statusOption->label() }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span class="indicator indicator-green"></span>
                  Public Response
                </label>
                <textarea name="note" class="form-textarea" placeholder="Write a response visible to the citizen..." required minlength="10">{{ old('note') }}</textarea>
                <div class="form-hint">Minimum 10 characters</div>
                @error('note')
                  <div class="form-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group documentation-field">
                <label class="form-label" for="documentation_images">Documentation proof</label>
                <p class="form-hint">Required when moving this complaint to <strong>In Progress</strong> or <strong>Resolved</strong>. Add up to five clear photos showing the work being done or completed.</p>
                <div class="documentation-sources" role="group" aria-label="Documentation photo sources">
                  <div class="documentation-source">
                    <span class="documentation-source__icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                    </span>
                    <span class="documentation-source__copy">
                      <strong>Photo library</strong>
                      <span>Select up to five existing images from this device.</span>
                    </span>
                    <button type="button" class="documentation-source__action" data-documentation-trigger="documentation_images">Choose photos</button>
                    <input class="documentation-file-input" type="file" name="documentation_images[]" id="documentation_images" accept="image/jpeg,image/png,image/webp" multiple>
                  </div>
                  <div class="documentation-source documentation-source--camera">
                    <span class="documentation-source__icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h3l1.5-2h7L17 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"></path><circle cx="12" cy="13" r="3.5"></circle></svg>
                    </span>
                    <span class="documentation-source__copy">
                      <strong>Use camera</strong>
                      <span>Capture a clear proof photo at the work site.</span>
                    </span>
                    <button type="button" class="documentation-source__action" data-documentation-trigger="documentation_camera_photos">Open camera</button>
                    <input class="documentation-file-input" type="file" name="documentation_camera_photos[]" id="documentation_camera_photos" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
                  </div>
                </div>
                <div class="documentation-selection" aria-live="polite">
                  <strong id="documentation-selected-count">0 / 5</strong>
                  <span>documentation photos selected</span>
                </div>
                <div class="documentation-previews" id="documentation-previews" aria-label="Selected documentation previews"></div>
                <div class="documentation-client-error" id="documentation-client-error" role="alert" hidden></div>
                @error('documentation_images')
                  <div class="form-error">{{ $message }}</div>
                @enderror
                @error('documentation_camera_photos')
                  <div class="form-error">{{ $message }}</div>
                @enderror
                @error('documentation_camera_photo')
                  <div class="form-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group internal-field">
                <label class="form-label">
                  <svg class="form-label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                  <span>Internal Note</span>
                </label>
                <textarea name="internal_note" class="form-textarea" placeholder="Staff-only note, not visible to citizen...">{{ old('internal_note') }}</textarea>
                <div class="internal-hint">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  Not visible to citizen
                </div>
              </div>

              <button type="submit" class="btn-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                Send Response & Update Status
              </button>
            </form>
          </div>
        </div>
        @endif

        {{-- Activity Timeline Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="card-header-title">Activity History</div>
          </div>
          <div class="card-body">
            @if($complaint->logs->isEmpty())
              <div class="timeline-empty">
                <svg class="timeline-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <div class="timeline-empty-title">No activity yet</div>
                <div class="timeline-empty-text">Be the first to respond to this complaint</div>
              </div>
            @else
              <div class="timeline">
                @foreach($complaint->logs->sortByDesc('created_at') as $log)
                  <div class="timeline-item {{ $log->is_internal ? 'internal' : '' }}">
                    <div class="timeline-avatar-row">
                      <div class="timeline-avatar">{{ substr($log->actor->full_name ?? 'S', 0, 1) }}</div>
                      <span class="timeline-actor-name">{{ $log->actor->full_name ?? 'System' }}</span>
                      <span class="timeline-role-badge">{{ $log->actor->role ?? 'staff' }}</span>
                    </div>

                    @if($log->previous_status && $log->new_status)
                      <span class="status-change-pill">{{ $log->previous_status }} → {{ $log->new_status }}</span>
                    @endif

                    @if($log->comment)
                      <div class="timeline-note">{{ $log->comment }}</div>
                    @endif

                    @if($log->documentation_images)
                      <div class="timeline-evidence" aria-label="Documentation photos">
                        @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                          <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener">
                            <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="Documentation photo {{ $documentationIndex + 1 }}" loading="lazy">
                          </a>
                        @endforeach
                      </div>
                    @endif

                    <span class="timeline-tag {{ $log->is_internal ? 'tag-internal' : 'tag-public' }}">
                      @if($log->is_internal)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        <span>Internal</span>
                      @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                        <span>Public</span>
                      @endif
                    </span>
                    <div class="timeline-time">{{ $log->created_at->diffForHumans() }}</div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{--
    Moderation, not publication.

    This used to be a checkbox that let a reviewer put a report on the public
    register. It is gone: whether a resident's complaint is public is their
    decision, and no review form can override it. What remains is the power to
    withhold or redact content that should not be broadcast, which is a
    different question and needs a stated reason.
--}}
<div class="moderation-panel">
  <h3 class="moderation-panel__title">Public presentation</h3>
  <p class="moderation-panel__lede">You can withhold this report or clean up sensitive parts of it. You cannot publish or unpublish it.</p>

  <p class="moderation-panel__consent">
    <strong>{{ $complaint->is_public ? 'The resident published this.' : 'The resident kept this private.' }}</strong>
    {{ $complaint->is_public
        ? 'It appears on the transparency page under their display alias. Moderating below can withhold it, but nothing here can change that choice.'
        : 'It does not appear on the transparency page, and nothing you do here can put it there.' }}
  </p>

  <form method="POST" action="{{ route('staff.complaints.moderate', $complaint) }}">
    @csrf

    <div class="moderation-field">
      <label class="moderation-check" for="moderation-hide">
        <input type="hidden" name="hidden_from_public" value="0">
        <input type="checkbox" id="moderation-hide" name="hidden_from_public" value="1" @checked($complaint->hidden_from_public)>
        <span class="moderation-check__text">Remove this report from the public transparency page</span>
      </label>
    </div>

    <div class="moderation-field">
      <label for="redacted_title">Replacement title for the public page</label>
      <input type="text" id="redacted_title" name="redacted_title" maxlength="255"
             value="{{ old('redacted_title', $complaint->redacted_title) }}"
             placeholder="{{ $complaint->title }}">
      <small class="visibility-panel__lede">Leave blank to show the original title unchanged.</small>
    </div>

    <div class="moderation-field">
      <label for="redacted_description">Replacement description for the public page</label>
      <textarea id="redacted_description" name="redacted_description" rows="4" maxlength="5000"
                placeholder="{{ $complaint->description }}">{{ old('redacted_description', $complaint->redacted_description) }}</textarea>
      <small class="visibility-panel__lede">Leave blank to show the original description unchanged.</small>
    </div>

    @if (count($complaint->allImagePaths()) > 0)
      <div class="moderation-field">
        <label>Evidence photos</label>
        <div class="moderation-photos">
          @foreach ($complaint->allImagePaths() as $path)
            @php $isWithheld = in_array($path, $complaint->withheldImagePaths(), true); @endphp
            <div class="moderation-photo">
              <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ basename($path) }}</span>
              @if ($isWithheld)
                <input type="hidden" name="restore_image_paths[]" value="{{ $path }}">
                <label style="margin: 0; color: var(--color-gold); cursor: pointer;">
                  <input type="checkbox" name="restore_image_paths[]" value="{{ $path }}"> Restore
                </label>
              @else
                <label style="margin: 0; cursor: pointer;">
                  <input type="checkbox" name="withheld_image_paths[]" value="{{ $path }}"> Withhold
                </label>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <div class="moderation-field">
      <label for="moderation_reason">Reason (required)</label>
      <textarea id="moderation_reason" name="moderation_reason" rows="2" maxlength="1000"
                placeholder="Why is this being withheld or cleaned up?">{{ old('moderation_reason', $complaint->moderation_reason) }}</textarea>
    </div>

    @error('moderation_reason') <div class="filing-error">{{ $message }}</div> @enderror

    <button type="submit" class="moderation-panel__submit">Save moderation</button>
  </form>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        /*
         * The location map.
         *
         * This was missing entirely: the page rendered a #complaint-map div and
         * the stylesheet for it, but never loaded Leaflet and never built a map
         * in it. A staff member dispatched to a complaint saw an empty box. The
         * coordinates are printed underneath it, so the work can still be done,
         * but the map looked broken rather than absent, which is how it survived
         * so long.
         *
         * The map is what a field worker recognises their destination by. The
         * digits are what they type into a handheld GPS when the phone has no
         * signal. Both are on this page deliberately.
         */
        const buildComplaintMap = () => {
            const mapElement = document.getElementById('complaint-map');
            if (!mapElement) return;
            if (mapElement.dataset.mapInitialized === 'true') return;
            mapElement.dataset.mapInitialized = 'true';

            const latitude = Number.parseFloat(mapElement.dataset.latitude);
            const longitude = Number.parseFloat(mapElement.dataset.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const map = window.L.map(mapElement, {
                scrollWheelZoom: false,
            }).setView([latitude, longitude], 16);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(map);

            window.L.marker([latitude, longitude]).addTo(map);

            // Leaflet measures its container once and caches that. A desktop
            // viewport has settled by then; a phone has not - the URL bar
            // collapses, scrollbars come and go, a late webfont reflows the card
            // around the map - and a map sized from a stale measurement renders
            // as a blank band or a strip of clipped tiles. `animate: false`
            // because a re-measure is not a user gesture and should not zoom the
            // map at them.
            const keepMapSized = () => map.invalidateSize({ animate: false });

            window.requestAnimationFrame(keepMapSized);
            window.setTimeout(keepMapSized, 300);
            window.addEventListener('resize', keepMapSized);
            window.addEventListener('orientationchange', () => {
                window.setTimeout(keepMapSized, 150);
            });
        };

        // Leaflet is bundled rather than fetched from a CDN, but a bundled
        // module is deferred and this inline script is not, so the map waits for
        // the callback rather than reaching for window.L and finding it undefined
        // on a page that is otherwise fine.
        const startComplaintMap = () => window.whenLeafletReady(buildComplaintMap, {
            onUnavailable: () => {
                const mapElement = document.getElementById('complaint-map');
                if (!mapElement) return;
                mapElement.innerHTML = '<div style="padding:20px;text-align:center;color:rgba(255,255,255,0.55);font-size:12px">Map unavailable. The coordinates below can still be entered in a handheld GPS.</div>';
            },
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startComplaintMap);
        } else {
            startComplaintMap();
        }

        const initializeDocumentationProof = () => {
            const form = document.getElementById('staff-response-form');
            if (!form || form.dataset.documentationProofInitialized === 'true') return;
            form.dataset.documentationProofInitialized = 'true';

            const galleryInput = document.getElementById('documentation_images');
            const cameraInput = document.getElementById('documentation_camera_photos');
            const previews = document.getElementById('documentation-previews');
            const count = document.getElementById('documentation-selected-count');
            const clientError = document.getElementById('documentation-client-error');
            const maxPhotos = 5;
            let galleryFiles = [];
            let cameraFiles = [];
            let previewUrls = [];

            const syncInput = (input, files) => {
                if (!input) return false;
                if (files.length === 0) {
                    input.value = '';
                    return true;
                }

                if (typeof DataTransfer === 'undefined') return false;

                const transfer = new DataTransfer();
                files.forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                return true;
            };

            const selectedFiles = () => [
                ...galleryFiles.map((file, index) => ({ file, source: 'gallery', index })),
                ...cameraFiles.map((file, index) => ({ file, source: 'camera', index })),
            ];

            const renderPreviews = () => {
                previewUrls.forEach((url) => URL.revokeObjectURL(url));
                previewUrls = [];
                if (!previews) return;

                previews.replaceChildren();
                selectedFiles().forEach(({ file, source, index }) => {
                    const preview = document.createElement('div');
                    preview.className = 'documentation-preview';

                    const image = document.createElement('img');
                    const objectUrl = URL.createObjectURL(file);
                    previewUrls.push(objectUrl);
                    image.src = objectUrl;
                    image.alt = `Selected documentation preview: ${file.name}`;
                    preview.appendChild(image);

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'documentation-preview-remove';
                    remove.setAttribute('aria-label', `Remove ${file.name}`);
                    remove.title = 'Remove photo';
                    remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                    remove.addEventListener('click', () => {
                        if (source === 'gallery') {
                            galleryFiles.splice(index, 1);
                            syncInput(galleryInput, galleryFiles);
                        } else {
                            cameraFiles.splice(index, 1);
                            syncInput(cameraInput, cameraFiles);
                        }
                        renderPreviews();
                    });
                    preview.appendChild(remove);
                    previews.appendChild(preview);
                });

                const files = selectedFiles();
                if (count) count.textContent = `${files.length} / ${maxPhotos}`;
                if (clientError) {
                    clientError.hidden = files.length <= maxPhotos;
                    clientError.textContent = files.length > maxPhotos
                        ? `Select no more than ${maxPhotos} documentation photos.`
                        : '';
                }
            };

            document.querySelectorAll('[data-documentation-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    document.getElementById(trigger.dataset.documentationTrigger)?.click();
                });
            });

            galleryInput?.addEventListener('change', () => {
                galleryFiles = Array.from(galleryInput.files || []);
                renderPreviews();
            });

            cameraInput?.addEventListener('change', () => {
                const incoming = Array.from(cameraInput.files || []);

                if (incoming.length === 1) {
                    cameraFiles.push(incoming[0]);
                } else {
                    const existingKeys = new Set(cameraFiles.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
                    incoming.forEach((file) => {
                        const key = `${file.name}-${file.size}-${file.lastModified}`;
                        if (!existingKeys.has(key)) {
                            cameraFiles.push(file);
                            existingKeys.add(key);
                        }
                    });
                }

                syncInput(cameraInput, cameraFiles);
                renderPreviews();
            });

            renderPreviews();
        };

        const scheduleDocumentationProof = () => window.setTimeout(initializeDocumentationProof, 80);
        document.addEventListener('livewire:navigated', scheduleDocumentationProof);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleDocumentationProof);
        } else {
            scheduleDocumentationProof();
        }
    })();
</script>
@endpush
