{{-- Sample report rows rendered with the exact classes/markup the register
     uses. Desktop width shows the grid; the 393px iframe shows the flex-wrap
     card layout — the same markup, only the CSS media query differs. --}}
@php
    $rows = [
        ['cat' => 'road_damage', 'ticket' => 'COMP-2026-00025', 'title' => 'This report concerns a significant hazard on the roadway located within Barangay Lag-on, which presents a daily risk to motorists and pedestrians. The city must repair this stretch of road.', 'dept' => 'ENG', 'deptName' => 'Engineering Office', 'barangay' => 'Lag-on', 'date' => 'Nov 8, 2026', 'status' => 'submitted', 'statusLabel' => 'Submitted', 'step' => 1, 'verified' => false],
        ['cat' => 'flooding', 'ticket' => 'COMP-2026-00022', 'title' => 'Large pothole along the national road', 'dept' => 'ENG', 'deptName' => 'Engineering Office', 'barangay' => 'VI (Centro)', 'date' => 'Nov 8, 2026', 'status' => 'in_progress', 'statusLabel' => 'In Progress', 'step' => 3, 'verified' => true],
        ['cat' => 'noise_complaint', 'ticket' => 'COMP-2026-00023', 'title' => 'Late-night noise near the public plaza', 'dept' => 'OM', 'deptName' => 'Office of the Mayor', 'barangay' => 'VI (Centro)', 'date' => 'Nov 8, 2026', 'status' => 'resolved', 'statusLabel' => 'Resolved', 'step' => 4, 'verified' => false],
        ['cat' => 'flooding', 'ticket' => 'COMP-2026-00005', 'title' => 'Flooded pedestrian path after rainfall', 'dept' => 'ENG', 'deptName' => 'Engineering Office', 'barangay' => 'VI (Centro)', 'date' => 'Nov 7, 2026', 'status' => 'submitted', 'statusLabel' => 'Submitted', 'step' => 1, 'verified' => false],
    ];
@endphp

<ul class="report-list" id="complaint-table-body">
    @foreach($rows as $row)
        @php
            $step = $row['step'];
            $isClosed = $row['status'] === 'closed';
            $isRejected = $row['status'] === 'rejected';
        @endphp
        <li data-report-row data-report-id="{{ $row['ticket'] }}" data-search="{{ strtolower($row['ticket'].' '.$row['title']) }}" data-department="{{ $row['deptName'] }}" data-category="{{ $row['cat'] }}" data-status="{{ $row['status'] }}" data-iso-date="2026-11-08T00:00:00+08:00" data-barangay="{{ $row['barangay'] }}">
            <a class="report-row" href="#" wire:navigate>
                <span class="report-row__complaint">
                    @include('pages.transparency.partials.category-icon', ['category' => $row['cat']])
                    <span class="report-row__text">
                        <span class="report-row__title">{{ \Illuminate\Support\Str::limit($row['title'], 90) }}</span>
                        <span class="report-row__meta">
                            <span class="ticket-id">{{ $row['ticket'] }}</span>
                            <span class="report-row__meta-dot" aria-hidden="true">·</span>
                            <svg class="report-row__meta-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="report-row__brgy">Barangay {{ $row['barangay'] }}</span>
                        </span>
                    </span>
                </span>
                <span class="report-row__dept">
                    <span class="dept-badge" data-dept="{{ $row['dept'] }}">{{ $row['dept'] }}</span>
                    <span class="report-row__dept-name">{{ $row['deptName'] }}</span>
                </span>
                <span class="report-row__status">
                    <span class="status-pill {{ $row['status'] }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $row['statusLabel'] }}</span>
                    @if($row['verified'])
                        <span class="report-row__verified"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>Verified</span>
                    @endif
                    <span class="progress progress--mini" aria-hidden="true">
                        @for($segment = 1; $segment <= 5; $segment++)
                            <span class="progress__seg {{ $segment < $step || $isClosed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '') }}"></span>
                        @endfor
                    </span>
                </span>
                <span class="report-row__reported"><time datetime="2026-11-08">{{ $row['date'] }}</time></span>
                <span class="report-row__progressline" role="img" aria-label="Step {{ $step }} of 5: {{ $row['statusLabel'] }}">
                    <span class="progress__text">Step {{ $step }} of 5 · {{ $row['statusLabel'] }}</span>
                    <span class="progress progress--full" aria-hidden="true">
                        @for($segment = 1; $segment <= 5; $segment++)
                            <span class="progress__seg {{ $segment < $step || $isClosed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '') }}"></span>
                        @endfor
                    </span>
                </span>
                <svg class="report-row__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </li>
    @endforeach
</ul>
