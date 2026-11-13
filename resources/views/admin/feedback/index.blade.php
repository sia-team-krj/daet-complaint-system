@extends('admin.layouts.app')

@section('title', __('admin_settings.feedback.title') . ' - ' . __('admin_settings.title'))

@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('admin_settings.feedback.title') }}</h1>
            <p class="admin-page-description">{{ __('admin_settings.feedback.lede') }}</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-metric-grid" aria-label="{{ __('admin_settings.feedback.ratings_title') }}">
        <div class="admin-metric">
            <span class="admin-metric-label">{{ __('admin_settings.feedback.col_average') }}</span>
            <strong class="admin-metric-value">{{ $portalAverage['count'] > 0 ? $portalAverage['average'] : '—' }}</strong>
            <small>{{ __('admin_settings.feedback.ratings_title') }}</small>
        </div>
        <div class="admin-metric">
            <span class="admin-metric-label">{{ __('admin_settings.feedback.col_count') }}</span>
            <strong class="admin-metric-value">{{ number_format($portalAverage['count']) }}</strong>
            <small>{{ $portalAverage['count'] > 0 ? __('feedback.average_now', ['average' => $portalAverage['average'], 'count' => $portalAverage['count']]) : __('feedback.average_none') }}</small>
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="feedback-office-ratings-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="feedback-office-ratings-title" class="admin-panel-title">{{ __('admin_settings.feedback.ratings_title') }}</h2>
            </div>
        </div>
        <div class="admin-panel-body">
            @if(count($officeAverages) > 0)
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin_settings.feedback.col_office') }}</th>
                                <th scope="col">{{ __('admin_settings.feedback.col_average') }}</th>
                                <th scope="col">{{ __('admin_settings.feedback.col_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($officeAverages as $row)
                                <tr>
                                    <td data-label="{{ __('admin_settings.feedback.col_office') }}">{{ $row['department']->name }}</td>
                                    <td data-label="{{ __('admin_settings.feedback.col_average') }}">{{ $row['average'] ?? '—' }}</td>
                                    <td data-label="{{ __('admin_settings.feedback.col_count') }}">{{ number_format($row['count']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="admin-muted-cell">{{ __('feedback.average_none') }}</p>
            @endif
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="feedback-pending-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="feedback-pending-title" class="admin-panel-title">{{ __('admin_settings.feedback.pending_title') }}</h2>
                <p class="admin-section-description">{{ __('admin_settings.feedback.pending_lede') }}</p>
            </div>
            @if($pending->total() > 0)
                <span class="admin-directory-count">{{ number_format($pending->total()) }}</span>
            @endif
        </div>
        <div class="admin-panel-body admin-form-stack">
            @forelse($pending as $suggestion)
                <article class="admin-feedback-item">
                    <p class="admin-feedback-item__body">{{ $suggestion->body }}</p>
                    <p class="admin-feedback-item__meta">
                        {{ $suggestion->user?->display_alias ?: __('feedback.community_member') }}
                        · {{ $suggestion->created_at->diffForHumans() }}
                    </p>
                    <div class="admin-feedback-item__actions">
                        <form data-loading-submit method="POST" action="{{ route('admin.feedback.publish', $suggestion) }}">
                            @csrf
                            <button type="submit" class="admin-button admin-button--primary admin-button--compact">{{ __('admin_settings.feedback.publish') }}</button>
                        </form>
                        <form data-loading-submit method="POST" action="{{ route('admin.feedback.withhold', $suggestion) }}">
                            @csrf
                            <button type="submit" class="admin-button admin-button--secondary admin-button--compact">{{ __('admin_settings.feedback.withhold') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="admin-empty-state">
                    <h3>{{ __('admin_settings.feedback.pending_empty') }}</h3>
                </div>
            @endforelse

            @if($pending->hasPages())
                <div class="admin-pagination-wrap">
                    <p class="admin-pager-summary" aria-live="polite">{{ __('pagination.page_of', ['page' => $pending->currentPage(), 'last' => $pending->lastPage(), 'count' => $pending->perPage()]) }}</p>
                    <div class="admin-pagination">{{ $pending->links('pagination::workspace', ['ariaLabel' => __('pagination.label')]) }}</div>
                </div>
            @endif
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="feedback-published-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="feedback-published-title" class="admin-panel-title">{{ __('admin_settings.feedback.published_title') }}</h2>
                <p class="admin-section-description">{{ __('admin_settings.feedback.published_lede') }}</p>
            </div>
        </div>
        <div class="admin-panel-body admin-form-stack">
            @forelse($published as $suggestion)
                <article class="admin-feedback-item admin-feedback-item--published">
                    <p class="admin-feedback-item__body">{{ $suggestion->body }}</p>
                    <p class="admin-feedback-item__meta">
                        {{ $suggestion->user?->display_alias ?: __('feedback.community_member') }}
                        · {{ $suggestion->published_at?->diffForHumans() }}
                    </p>
                </article>
            @empty
                <div class="admin-empty-state">
                    <h3>{{ __('admin_settings.feedback.published_empty') }}</h3>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
