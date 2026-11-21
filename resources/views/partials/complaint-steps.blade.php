{{--
    The five-step complaint trail.

    Shared by the public transparency detail, the resident ticket and the
    guest tracker so the three pages cannot drift. `$steps` is the array
    returned by App\Support\ComplaintProgress::steps().
--}}
<ol class="pt-steps">
    @foreach($steps as $index => $step)
        <li class="pt-step pt-step--{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
            <span class="pt-step__marker" aria-hidden="true">
                @if($step['state'] === 'complete')
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                @elseif($step['state'] === 'rejected')
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="m7 7 10 10"/><path d="m17 7-10 10"/></svg>
                @elseif($step['state'] === 'current')
                    <span class="pt-step__current-dot"></span>
                @else
                    {{ $index + 1 }}
                @endif
            </span>
            <span class="pt-step__text">
                <span class="pt-step__label">{{ $step['label'] }}</span>
                <span class="pt-step__caption">
                    @if($step['state'] === 'complete')
                        {{ $step['date'] ?? __('transparency.show.done') }}
                    @elseif($step['state'] === 'current')
                        {{ __('transparency.show.current_stage') }}
                    @elseif($step['state'] === 'rejected')
                        {{ __('transparency.show.rejected_stage') }}
                    @else
                        {{ __('transparency.show.upcoming') }}
                    @endif
                </span>
            </span>
        </li>
    @endforeach
</ol>
