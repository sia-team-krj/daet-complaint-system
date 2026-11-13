{{--
    The Everyone state in the audit directory.

    The trail below is still every record; this panel explains how to narrow it
    without dumping every person in the log into the page.
--}}
<div class="admin-audit-browser__prompt" role="status">
    <span class="admin-audit-browser__prompt-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </span>
    <div>
        <strong>{{ __('audit.directory_prompt_title') }}</strong>
        <p>{{ __('audit.directory_prompt_body') }}</p>
    </div>
</div>
