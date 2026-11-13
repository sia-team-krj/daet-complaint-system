{{--
    One person selected in the audit directory.

    The trail below is filtered to them; repeating the roster here would only
    repeat the same name and count in a second place.
--}}
<div class="admin-audit-browser__prompt admin-audit-browser__prompt--selected" role="status">
    <span class="admin-audit-browser__prompt-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </span>
    <div>
        <strong>{{ $selection['label'] }}</strong>
        <p>{{ __('audit.selection_trail', ['entries' => trans_choice('audit.entries', $selection['total'])]) }}</p>
        <a href="{{ route('admin.audit.index') }}" class="admin-audit-browser__prompt-clear">{{ __('audit.clear_selection') }}</a>
    </div>
</div>
