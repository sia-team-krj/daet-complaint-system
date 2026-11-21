{{--
    Progress-only timeline for the public ticket tracker.

    Deliberately narrower than the transparency/resident timeline: no evidence
    photos, no actor names, no internal notes. Only the status, the date, and
    the comment the office marked public.
--}}
@if($updates->isEmpty())
    <div class="pt-empty">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <strong>{{ __('transparency.show.no_updates') }}</strong>
        <span>{{ __('transparency.show.next_update') }}</span>
    </div>
@else
    <ul class="pt-updates">
        @foreach($updates as $log)
            @php
                $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status);
                $statusKey = str((string) $log->new_status)->slug();
            @endphp
            <li class="pt-update{{ $loop->first ? ' pt-update--newest' : '' }}">
                <span class="pt-update__marker" aria-hidden="true"></span>
                <div>
                    <div class="pt-update__meta">
                        @if($loop->first)
                            <span class="track-update-latest">{{ __('complaint_track.result.latest') }}</span>
                        @endif
                        <span class="pt-pill pt-pill--{{ $statusKey }}">
                            <span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}
                        </span>
                        <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->translatedFormat('M j, Y g:i A') }}</time>
                    </div>
                    @if($log->publicDisplayComment())
                        <p class="pt-update__text">{{ $log->publicDisplayComment() }}</p>
                    @endif
                    @if(($showDocumentation ?? false) && !empty($log->documentation_images))
                        <div class="pt-evidence" aria-label="{{ __('transparency.show.documentation') }}">
                            @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                <a href="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                    <img src="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('transparency.show.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy" width="420" height="420">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
