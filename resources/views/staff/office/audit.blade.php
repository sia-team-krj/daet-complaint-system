@extends('staff.layouts.app')

@section('title', __('office.audit.title') . ' - Daet Listens')
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <div class="office-banner">
                <div>
                    <h1 class="tr-h1">{{ __('office.audit.title') }}</h1>
                    <p class="tr-privacy">{{ __('office.audit.lede', ['office' => $office->name]) }}</p>
                </div>
                <a href="{{ route('staff.office.index') }}" class="staff-button staff-button--secondary">{{ __('office.audit.back') }}</a>
            </div>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        @include('staff.partials.flash')

        <section class="tr-card office-panel" aria-labelledby="office-audit-list-title">
            <div class="office-panel__header">
                <div>
                    <h2 class="tr-h2 office-panel__title" id="office-audit-list-title">{{ __('office.audit.title') }}</h2>
                    <p class="office-panel__description">{{ __('office.audit.description') }}</p>
                </div>
            </div>

            @if($logs->isEmpty())
                <p class="office-empty">{{ __('office.audit.empty') }}</p>
            @else
                <ul class="office-audit-list">
                    @foreach($logs as $log)
                        @php
                            $actionKey = 'audit.actions.' . $log->action;
                            $actionLabel = \Illuminate\Support\Facades\Lang::has($actionKey)
                                ? __($actionKey)
                                : \Illuminate\Support\Str::headline(str_replace('.', ' ', (string) $log->action));
                            $actorRole = $log->actor?->role ?? $log->actor_role;
                            $actorRoleKey = $actorRole ? 'roles.' . $actorRole : null;
                            $actorRoleLabel = $actorRoleKey && \Illuminate\Support\Facades\Lang::has($actorRoleKey)
                                ? __($actorRoleKey)
                                : __('office.audit.system');
                            $actorName = $log->actor?->full_name ?? $log->actor_name ?? __('office.audit.system');
                        @endphp
                        <li class="office-audit-item">
                            <div class="office-audit-item__main">
                                <div class="office-audit-item__top">
                                    <p class="office-member__name">{{ $actionLabel }}</p>
                                    <time class="office-member__meta" datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->format('M j, Y g:i A') }}</time>
                                </div>
                                <p class="office-audit-item__copy">{{ $log->description }}</p>
                                <div class="office-member__badges">
                                    <span class="office-badge">{{ $actorName }}</span>
                                    <span class="office-badge office-badge--role">{{ $actorRoleLabel }}</span>
                                    @if($log->department)
                                        <span class="office-badge">{{ $log->department->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="office-pagination" aria-label="{{ __('office.audit.pagination') }}">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
