@php
  $statusEnum = $complaint->statusEnum;
  $statusValue = $statusEnum->value;
  $reviewValue = $complaint->reviewStatusEnum->value;
  $categoryLabels = collect(\App\Services\DepartmentRouter::getCategoryOptions())->flatten()->pluck('label', 'value');
  $categoryLabel = $categoryLabels->get($complaint->category, ucwords(str_replace('_',' ', (string)$complaint->category)));
  $createdAt = $complaint->created_at->copy()->setTimezone('Asia/Manila');
  $isResolved = in_array($statusValue, ['Resolved','Closed']);
  $isRejected = $statusValue === 'Rejected';
  $step = match(true) {
    $isResolved => 5,
    $statusValue === 'In Progress' => 4,
    !empty($complaint->assigned_staff_id) => 3,
    $reviewValue === 'verified' || $statusValue === 'Under Review' => 2,
    default => 1,
  };
  if ($isRejected) $step = 1;
  $pillClass = $isRejected ? 'db-pill--rejected' : match($statusValue) { 'Submitted'=>'db-pill--submitted', 'Under Review'=>'db-pill--verified', 'In Progress'=>'db-pill--in-progress', 'Resolved'=>'db-pill--resolved', default=>'db-pill--submitted' };
  if (!empty($complaint->assigned_staff_id) && $statusValue !== 'In Progress' && !$isResolved && !$isRejected) $pillClass = 'db-pill--assigned';
  $steps = __('dashboard.steps');
@endphp
<li>
  <a href="{{ route('complaints.show', $complaint) }}" wire:navigate class="db-card report-card" aria-label="{{ __('dashboard.view_report') }} {{ $complaint->ticket_id }}: {{ $complaint->title }}">
    <span class="db-card__body">
      <span class="db-card__row1">
        <span class="db-ticket">{{ $complaint->ticket_id }}</span>
        <span class="db-pill {{ $pillClass }}">{{ $statusEnum->label() }}</span>
      </span>
      <span class="db-card__title">{{ $complaint->title }}</span>
      <span class="db-card__meta">
        <span>{{ $categoryLabel }}</span>
        <span>{{ $complaint->department->name ?? __('dashboard.awaiting_routing') }}</span>
        <span>{{ __('dashboard.filed') }} {{ $createdAt->translatedFormat('M j, Y') }}</span>
      </span>
      <span class="db-progress" aria-hidden="true">
        @foreach($steps as $i => $stepLabel)
          <span class="db-progress__step"><span class="db-progress__dot {{ $i+1 < $step ? 'db-progress__dot--done' : ($i+1 === $step ? 'db-progress__dot--current' : '') }}"></span>{{ $stepLabel }}</span>
          @if($i < count($steps)-1)<span class="db-progress__line {{ $i+1 < $step ? 'db-progress__line--done' : '' }}"></span>@endif
        @endforeach
      </span>
      <span class="db-progress-mobile" role="img" aria-label="{{ trans('dashboard.step_of', ['current'=>$step,'total'=>5,'label'=>$steps[$step-1]]) }}">
        <span class="db-progress-mobile__label">{{ trans('dashboard.step_of', ['current'=>$step,'total'=>5,'label'=>$steps[$step-1]]) }}</span>
        <span class="db-progress-segments" aria-hidden="true">
          @foreach($steps as $i => $stepLabel)
            <span class="db-progress-segment {{ $i+1 < $step ? 'is-done' : ($i+1 === $step ? 'is-current' : '') }}"></span>
          @endforeach
        </span>
        <span class="db-card__date-mobile">{{ $createdAt->translatedFormat('M j') }}</span>
      </span>
      <span class="sr-only">{{ trans('dashboard.step_of', ['current'=>$step,'total'=>5,'label'=>$steps[$step-1]]) }}</span>
    </span>
    <svg class="db-card__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
  </a>
</li>
