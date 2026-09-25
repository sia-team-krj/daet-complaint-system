@extends('admin.layouts.app')

@section('title', 'Staff Invitations — Admin')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Staff invitations</h1>
            <p class="admin-page-description">Create a secure, single-use link for a staff member to join a specific department.</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <div class="admin-two-column-layout admin-two-column-layout--invitation">
        <section class="admin-section admin-section--flush" aria-labelledby="invitation-list-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="invitation-list-title" class="admin-section-title">Issued invitations</h2>
                    <p class="admin-section-description">Every code is bound to its department, expires in 10 minutes, and can create one account.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Department</th><th>Invitation link</th><th>Expires</th><th>Status</th><th><span class="sr-only">Action</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse($invitations as $invitation)
                            <tr>
                                <td><span class="admin-ticket-id">{{ $invitation->department?->name ?? 'Unavailable department' }}</span></td>
                                <td>
                                    <button type="button" class="admin-copy-code" data-copy-link="{{ route('invitations.show', $invitation->code) }}" title="Copy invitation link">
                                        <span>{{ \Illuminate\Support\Str::limit($invitation->code, 12, '…') }}</span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </td>
                                <td class="admin-muted-cell">{{ $invitation->expires_at?->format('M j, Y g:i A') }}</td>
                                <td><span class="admin-status admin-status--{{ str($invitation->statusLabel())->slug() }}">{{ $invitation->statusLabel() }}</span></td>
                                <td>
                                    @if($invitation->is_active)
                                        <form method="POST" action="{{ route('admin.invitations.revoke', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="admin-button admin-button--compact admin-button--danger-ghost">Revoke</button>
                                        </form>
                                    @else
                                        <span class="admin-muted-cell">No action needed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="admin-empty-cell">No staff invitations have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invitations->hasPages())
                <div class="admin-pagination">{{ $invitations->links() }}</div>
            @endif
        </section>

        <aside>
            <section class="admin-panel" aria-labelledby="create-invitation-title">
                <div class="admin-panel-heading">
                    <h2 id="create-invitation-title" class="admin-panel-title">New invitation</h2>
                </div>
                <form method="POST" action="{{ route('admin.invitations.store') }}" class="admin-panel-body admin-form-stack">
                    @csrf
                    <label class="admin-field">
                        <span>Department</span>
                        <select name="department_id" class="admin-input" required>
                            <option value="">Select a department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="admin-button admin-button--primary">Generate invitation link</button>
                </form>
            </section>

            <section class="admin-info-panel">
                <h2>How invitations work</h2>
                <ul>
                    <li>The recipient creates their own password.</li>
                    <li>The account is assigned to the selected department.</li>
                    <li>The link expires after 10 minutes.</li>
                    <li>The link is invalid immediately after one redemption.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const copyButtons = document.querySelectorAll('[data-copy-link]');

        copyButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                const originalText = button.querySelector('span')?.textContent;
                try {
                    await navigator.clipboard.writeText(button.dataset.copyLink);
                    if (originalText) button.querySelector('span').textContent = 'Copied';
                } catch {
                    window.prompt('Copy this invitation link:', button.dataset.copyLink);
                }

                window.setTimeout(() => {
                    if (originalText) button.querySelector('span').textContent = originalText;
                }, 1800);
            });
        });
    })();
</script>
@endpush
