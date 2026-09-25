@if (session('status'))
    <div class="staff-flash staff-flash--success" role="status">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="staff-flash staff-flash--error" role="alert">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif
