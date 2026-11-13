@extends('staff.layouts.app')

@section('title', __('staff.activity.department_title') . ' - ' . __('staff.title'))
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">{{ __('staff.activity.department_title') }}</h1>
            <p class="tr-privacy">
                {{ $department->name }}
                — {{ trans_choice('staff.activity.department_lede', $stats['actions'], ['count' => $stats['actions']]) }}
            </p>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        {{-- The same three metrics as "My activity", asked of the office
             instead of the member. --}}
        <section class="staff-metric-grid staff-metric-grid--three" aria-label="{{ __('staff.activity.department_title') }}" style="margin-top: -16px; margin-bottom: 24px;">
            <article class="staff-metric"><span>{{ __('staff.activity.metric_actions') }}</span><strong>{{ $stats['actions'] }}</strong></article>
            <article class="staff-metric"><span>{{ __('staff.activity.metric_complaints') }}</span><strong>{{ $stats['complaints'] }}</strong></article>
            <article class="staff-metric"><span>{{ __('staff.activity.metric_last') }}</span><strong class="staff-metric-text">{{ $stats['lastAction']?->diffForHumans() ?? '-' }}</strong></article>
        </section>

        @include('staff.partials.flash')

        <div class="tr-register-head" style="margin-top: 16px;">
            <h2 class="tr-h2" id="department-activity-title">{{ __('staff.activity.department_feed_title') }}</h2>
            <p class="tr-results">{{ __('staff.activity.department_feed_lede') }}</p>
        </div>

        {{-- The same feed the dashboard card shows, in full. --}}
        <div class="tr-card dept-activity" style="margin-top: 12px;">
            <div class="dept-activity__head">
                <h3 class="dept-section-title">{{ __('staff.activity.department_feed_title') }}</h3>
            </div>
            <div class="dept-activity__feed" id="dept-activity-feed">
                @include('staff.partials.activity-feed', ['entries' => $entries])
            </div>
        </div>

        @if ($logs->hasPages())
            <div class="tr-pagination">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
