@extends('admin.layouts.app')

@section('title', 'Staff Invitations - Admin')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Staff invitations</h1>
            <p class="admin-page-description">Email a secure, single-use link so a staff member can register into a specific department.</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <div class="admin-two-column-layout admin-two-column-layout--invitation">
        <section class="admin-section admin-section--flush" aria-labelledby="invitation-list-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="invitation-list-title" class="admin-section-title">Issued invitations</h2>
                    <p class="admin-section-description">Every code is emailed to one address, bound to its department, expires in {{ config('invitations.expires_in_hours', 24) }} hours, and can create one account.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Invitee</th><th>Department</th><th>Invitation link</th><th>Expires</th><th>Status</th><th><span class="sr-only">Action</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse($invitations as $invitation)
                            <tr>
                                <td>
                                    <span class="admin-ticket-id">{{ $invitation->inviteeLabel() }}</span>
                                    <span class="admin-muted-cell">
                                        {{ $invitation->wasEmailed() ? 'Emailed '.$invitation->emailed_at->format('M j, g:i A') : 'Not emailed' }}
                                    </span>
                                </td>
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
                                        <form data-loading-submit method="POST" action="{{ route('admin.invitations.revoke', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
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
                            <tr><td colspan="6" class="admin-empty-cell">No staff invitations have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invitations->hasPages())
                <div class="admin-pagination">{{ $invitations->links('pagination::workspace', ['ariaLabel' => 'Invitation pages']) }}</div>
            @endif
        </section>

        <aside>
            <section class="admin-panel" aria-labelledby="create-invitation-title">
                <div class="admin-panel-heading">
                    <h2 id="create-invitation-title" class="admin-panel-title">Invite one person</h2>
                </div>
                <form data-loading-submit method="POST" action="{{ route('admin.invitations.store') }}" class="admin-panel-body admin-form-stack admin-invitation-form">
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
                    <label class="admin-field">
                        <span>Name <small>Optional</small></span>
                        <input type="text" name="name" class="admin-input" value="{{ old('name') }}" maxlength="100" placeholder="Juan Dela Cruz" autocomplete="off">
                        @error('name')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="admin-field">
                        <span>Email address</span>
                        <input type="email" name="email" class="admin-input" value="{{ old('email') }}" maxlength="255" required placeholder="staff@daet.gov.ph" autocomplete="off">
                        @error('email')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="admin-button admin-button--primary">Email one invitation</button>
                </form>
            </section>

            <section class="admin-info-panel">
                <h2>How invitations work</h2>
                <ul>
                    <li>Use the form above for one person.</li>
                    <li>Use the bulk section for several at once.</li>
                    <li>The recipient creates their own password.</li>
                    <li>The link expires after {{ config('invitations.expires_in_hours', 24) }} hours.</li>
                    <li>The link is invalid immediately after one redemption.</li>
                </ul>
            </section>
        </aside>
    </div>

    <section class="admin-section admin-invitation-bulk" aria-labelledby="bulk-invitation-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="bulk-invitation-title" class="admin-section-title">Invite several people at once</h2>
                <p class="admin-section-description">Paste up to 25 email addresses, one per line, and check the list before anything is sent.</p>
            </div>
        </div>

        <form data-loading-submit method="POST" action="{{ route('admin.invitations.preview') }}" class="admin-form-stack admin-invitation-form">
            @csrf
            <div class="admin-invitation-bulk-grid">
                <label class="admin-field">
                    <span>Department for every address</span>
                    <select name="default_department_id" class="admin-input">
                        <option value="">Each line names its own department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('default_department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <span class="admin-field-hint">Pick one to apply it to every line, or leave it for a mixed list.</span>
                </label>

                <label class="admin-field">
                    <span>Email addresses</span>
                    <textarea name="recipients" class="admin-input admin-invitation-textarea" rows="8" required placeholder="juan@daet.gov.ph&#10;maria@daet.gov.ph | General Services&#10;Pedro Reyes &lt;pedro@daet.gov.ph&gt; | Engineering">{{ old('recipients') }}</textarea>
                    <span class="admin-field-hint">One per line. Add " | Department" only when the list is mixed.</span>
                    @error('recipients')<span class="admin-field-error">{{ $message }}</span>@enderror
                </label>
            </div>

            <div>
                <button type="submit" class="admin-button admin-button--primary">Check list</button>
                <span class="admin-field-hint">Nothing is emailed until you confirm the checked list below.</span>
            </div>
        </form>

        @if(!empty($preview))
            <div class="admin-invitation-review">
                <h3 class="admin-invitation-review-title">Review before sending</h3>

                <ul class="admin-invitation-tally">
                    <li><strong>{{ $preview['ready'] }}</strong> ready</li>
                    <li><strong>{{ $preview['skipped'] }}</strong> skipped</li>
                    <li><strong>{{ $preview['invalid'] }}</strong> cannot send</li>
                </ul>

                <div class="admin-table-wrap">
                    <table class="admin-table admin-invitation-review-table">
                        <thead>
                            <tr><th>Line</th><th>Invitee</th><th>Department</th><th>Result</th></tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr>
                                    <td class="admin-muted-cell">{{ $row['line'] }}</td>
                                    <td>
                                        <span class="admin-ticket-id">{{ $row['email'] ?? '-' }}</span>
                                        @if(!empty($row['name']))
                                            <span class="admin-muted-cell">{{ $row['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="admin-muted-cell">{{ $row['department_name'] ?? '-' }}</td>
                                    <td>
                                        <span class="admin-status admin-status--{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span>
                                        @if(!empty($row['reason']))
                                            <span class="admin-muted-cell">{{ $row['reason'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($preview['ready'] > 0)
                    <form data-loading-submit method="POST" action="{{ route('admin.invitations.send') }}" class="admin-invitation-confirm">
                        @csrf
                        <button type="submit" class="admin-button admin-button--primary">
                            Yes, email {{ $preview['ready'] }} invitation{{ $preview['ready'] === 1 ? '' : 's' }}
                        </button>
                    </form>
                @endif

                <p class="admin-field-hint">Sending emails cannot be undone. Each link works once and only for the address shown.</p>
            </div>
        @endif
    </section>
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
