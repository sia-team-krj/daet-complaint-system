{{--
    The live public register.

    Everything in here is derived from one query in TransparencyFeed, so all of
    it re-renders together. Anything on this page that does NOT come from that
    query - the styles, the footer, the map and filter scripts - stays in the
    page view, because a component that re-renders a timer does not need to
    re-render the static chrome along with it.

    The map payload below is a data attribute rather than the
    <script type="application/json"> island it used to be, and that is not a
    stylistic choice. Livewire treats a <script> it finds inside a component as
    code to evaluate, and this file is JSON, not code. An attribute has no such
    ambiguity: it is text, it round-trips through the morph untouched, and the
    page script reads it with JSON.parse.
--}}
<div class="transparency-root"
     wire:poll.{{ \App\Livewire\TransparencyRegister::REFRESH_SECONDS }}s.visible>
    <header class="transparency-header">
        <div class="transparency-container transparency-header__inner">
            <div class="transparency-header__copy">
                <span class="transparency-kicker">{{ __('transparency.header.kicker') }}</span>
                <h1>{{ __('transparency.header.title_before') }} <em>{{ __('transparency.header.title_emphasis') }}</em></h1>
                <p class="transparency-header__lede">
                    {{ __('transparency.header.lede') }}
                </p>
                <div class="transparency-header__meta">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V4.5"></path><path d="M4 19.5h16"></path><path d="m7 15 3-4 3 2 4-6"></path></svg>
                        {{ $reports->count() }} {{ $reports->count() === 1 ? __('transparency.header.report_one') : __('transparency.header.report_other') }}
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                        {{ __('transparency.header.updated') }} {{ $lastUpdated }}
                    </span>
                </div>
            </div>
            <div class="transparency-header__actions">
                @auth
                    <a href="{{ route('complaints.index') }}" class="transparency-button transparency-button--gold">{{ __('transparency.header.my_complaints') }}</a>
                    <a href="{{ route('complaints.create') }}" class="transparency-button transparency-button--quiet">{{ __('transparency.header.file_complaint') }}</a>
                @else
                    <a href="{{ route('login') }}" class="transparency-button transparency-button--gold">{{ __('transparency.header.log_in') }}</a>
                    <a href="{{ route('register') }}" class="transparency-button transparency-button--quiet">{{ __('transparency.header.create_account') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <section class="transparency-section transparency-section--map" id="heatmap" aria-labelledby="heatmap-heading">
        <div class="transparency-container">
            <div class="transparency-section__head">
                <div>
                    <span class="transparency-kicker">{{ __('transparency.map.kicker') }}</span>
                    <h2 id="heatmap-heading">{{ __('transparency.map.heading') }}</h2>
                    <p>{{ __('transparency.map.lede') }}</p>
                </div>
            </div>

            <div class="transparency-map-card">
                <div class="transparency-map-card__head">
                    <h3>
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                        {{ __('transparency.map.card') }}
                    </h3>
                    <div class="transparency-map-legend" aria-label="{{ __('transparency.map.legend') }}">
                        <span>{{ __('transparency.map.legend_low') }}</span>
                        <span class="transparency-map-legend__scale" aria-hidden="true"></span>
                        <span>{{ __('transparency.map.legend_high') }}</span>
                    </div>
                </div>
                <div class="transparency-map-frame">
                    <div id="transparency-map" role="region" aria-label="{{ __('transparency.map.map_aria') }}"></div>
                    <div class="transparency-map-status" id="transparency-map-status" aria-live="polite">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                        <span>{{ __('transparency.map.loading') }}</span>
                    </div>
                </div>
                <div class="transparency-map-card__foot">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    <span>{{ __('transparency.map.coordinates') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="transparency-section transparency-section--table" id="complaints" aria-labelledby="complaints-heading">
        <div class="transparency-container">
            <div class="transparency-section__head">
                <div>
                    <span class="transparency-kicker">{{ __('transparency.register.kicker') }}</span>
                    <h2 id="complaints-heading">{{ __('transparency.register.heading') }}</h2>
                    <p>{{ __('transparency.register.subtitle') }}</p>
                </div>
            </div>

            <div class="transparency-table-card">
                <div class="transparency-table-toolbar">
                    <div class="transparency-field">
                        <label for="complaint-search">{{ __('transparency.register.search_label') }}</label>
                        <input id="complaint-search" type="search" placeholder="{{ __('transparency.register.search_placeholder') }}" autocomplete="off">
                    </div>
                    <div class="transparency-field">
                        <label for="complaint-department">{{ __('transparency.register.department_label') }}</label>
                        <select id="complaint-department">
                            <option value="">{{ __('transparency.register.all_departments') }}</option>
                            @foreach($departmentOptions as $department)
                                <option value="{{ $department }}">{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="transparency-field">
                        <label for="complaint-category">{{ __('transparency.register.category_label') }}</label>
                        <select id="complaint-category">
                            <option value="">{{ __('transparency.register.all_categories') }}</option>
                            @foreach($categoryOptions as $category)
                                <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="transparency-field">
                        <label for="complaint-status">{{ __('transparency.register.status_label') }}</label>
                        <select id="complaint-status">
                            <option value="">{{ __('transparency.register.all_statuses') }}</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status['key'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button id="complaint-reset" class="transparency-reset" type="button">{{ __('transparency.register.reset') }}</button>
                </div>

                <div class="transparency-table-meta">
                    <span id="complaint-results">{{ __('transparency.register.results', ['visible' => $reports->count(), 'total' => $reports->count()]) }}</span>
                    <span>{{ __('transparency.register.visibility_only') }}</span>
                </div>

                <div class="transparency-table-scroll transparency-desktop-table">
                    <table class="transparency-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('transparency.register.table.ticket') }}</th>
                                <th scope="col">{{ __('transparency.register.table.complaint') }}</th>
                                <th scope="col">{{ __('transparency.register.table.category') }}</th>
                                <th scope="col">{{ __('transparency.register.table.department') }}</th>
                                <th scope="col">{{ __('transparency.register.table.location') }}</th>
                                <th scope="col">{{ __('transparency.register.table.status') }}</th>
                                <th scope="col">{{ __('transparency.register.table.reported') }}</th>
                            </tr>
                        </thead>
                        <tbody id="complaint-table-body">
                            @forelse($reports as $report)
                                <tr
                                    data-report-row
                                    data-report-id="{{ $report['ticket_id'] }}"
                                    data-search="{{ strtolower(implode(' ', [$report['ticket_id'], $report['title'], $report['category_label'], $report['department'], $report['location'], $report['reporter']])) }}"
                                    data-department="{{ $report['department'] }}"
                                    data-category="{{ $report['category'] }}"
                                    data-status="{{ $report['status_key'] }}"
                                >
                                    <td class="transparency-cell-ticket" data-label="Ticket">
                                        <a href="{{ $report['url'] }}" class="transparency-ticket">{{ $report['ticket_id'] }}</a>
                                    </td>
                                    <td class="transparency-cell-complaint" data-label="Complaint">
                                        <a href="{{ $report['url'] }}" class="transparency-complaint-title">{{ $report['title'] }}</a>
                                        <span class="transparency-reporter">{{ $report['reporter'] }}</span>
                                        <a href="{{ $report['url'] }}" class="transparency-row-link" aria-label="{{ __('transparency.register.open_ticket', ['ticket' => $report['ticket_id']]) }}">{{ __('transparency.register.view_details') }}</a>
                                    </td>
                                    <td class="transparency-cell-category transparency-table-muted" data-label="Category">{{ $report['category_label'] }}</td>
                                    <td class="transparency-cell-department transparency-table-muted" data-label="Department">{{ $report['department'] }}</td>
                                    <td class="transparency-cell-location transparency-table-muted" data-label="Location">{{ $report['location'] }}</td>
                                    <td class="transparency-cell-status" data-label="Status">
                                        <span class="transparency-status transparency-status--{{ $report['status_key'] }}">{{ $report['status_label'] }}</span>
                                        <span class="transparency-verification transparency-verification--{{ $report['verification_key'] }}">{{ $report['verification'] }}</span>
                                    </td>
                                    <td class="transparency-cell-date transparency-table-muted" data-label="Reported"><time datetime="{{ $report['iso_date'] }}">{{ $report['date'] }}</time></td>
                                </tr>
                            @empty
                                <tr id="complaint-empty-row" class="transparency-empty-row">
                                    <td colspan="7">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V4.5"></path><path d="M4 19.5h16"></path><path d="m7 15 3-4 3 2 4-6"></path></svg>
                                        <strong>{{ __('transparency.register.empty.title') }}</strong>
                                        <p>{{ __('transparency.register.empty.body') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                            <tr id="complaint-no-results" data-no-results class="transparency-empty-row" hidden>
                                <td colspan="7">
                                    <strong>{{ __('transparency.register.no_results.title') }}</strong>
                                    <p>{{ __('transparency.register.no_results.body') }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="transparency-mobile-list" aria-label="{{ __('transparency.register.mobile_list') }}">
                    @forelse($reports as $report)
                        <article
                            class="transparency-mobile-card"
                            data-report-row
                            data-report-id="{{ $report['ticket_id'] }}"
                            data-search="{{ strtolower(implode(' ', [$report['ticket_id'], $report['title'], $report['category_label'], $report['department'], $report['location'], $report['reporter']])) }}"
                            data-department="{{ $report['department'] }}"
                            data-category="{{ $report['category'] }}"
                            data-status="{{ $report['status_key'] }}"
                        >
                            <div class="transparency-mobile-card__top">
                                <a href="{{ $report['url'] }}" class="transparency-mobile-ticket">{{ $report['ticket_id'] }}</a>
                                <span class="transparency-status transparency-status--{{ $report['status_key'] }}">{{ $report['status_label'] }}</span>
                            </div>
                            <a href="{{ $report['url'] }}" class="transparency-mobile-card__title">{{ $report['title'] }}</a>
                            <span class="transparency-mobile-card__reporter">{{ $report['reporter'] }}</span>
                            <dl class="transparency-mobile-card__meta">
                                <div><dt>{{ __('transparency.register.table.category') }}</dt><dd>{{ $report['category_label'] }}</dd></div>
                                <div><dt>{{ __('transparency.register.table.department') }}</dt><dd>{{ $report['department'] }}</dd></div>
                                <div><dt>{{ __('transparency.register.table.location') }}</dt><dd>{{ $report['location'] }}</dd></div>
                                <div><dt>{{ __('transparency.register.table.reported') }}</dt><dd><time datetime="{{ $report['iso_date'] }}">{{ $report['date'] }}</time></dd></div>
                            </dl>
                            <div class="transparency-mobile-card__footer">
                                <span class="transparency-table-muted">{{ __('transparency.register.visibility_only') }}</span>
                                <a href="{{ $report['url'] }}" class="transparency-row-link" aria-label="{{ __('transparency.register.open_ticket', ['ticket' => $report['ticket_id']]) }}">{{ __('transparency.register.view_details') }}</a>
                            </div>
                        </article>
                    @empty
                        <div class="transparency-mobile-empty">
                            <strong>{{ __('transparency.register.empty.title') }}</strong>
                            <p>{{ __('transparency.register.empty.body') }}</p>
                        </div>
                    @endforelse
                    <div data-no-results class="transparency-mobile-empty" hidden>
                        <strong>{{ __('transparency.register.no_results.title') }}</strong>
                        <p>{{ __('transparency.register.no_results.body') }}</p>
                    </div>
                </div>
            </div>

            <p class="transparency-table-note">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                <span>
                    {{ __('transparency.register.private_note') }} @auth <a href="{{ route('complaints.index') }}">{{ __('transparency.header.my_complaints') }}</a> {{ __('transparency.register.private_note_authenticated') }} @else <a href="{{ route('login') }}">{{ __('transparency.header.log_in') }}</a> {{ __('transparency.register.private_note_guest') }} @endauth
                </span>
            </p>
        </div>
    </section>

    

    {{-- The map's payload. It lives INSIDE the polled root on purpose: it is the
         same data as the rows, and if it sat outside, the rows would refresh
         around a heat map still describing the complaints it was first handed.
         Read by the page script as data attributes rather than a JSON <script>
         island, because Livewire treats a script tag inside a component as code
         to evaluate and this is not code. --}}
    <div id="transparency-map-data"
         data-center="{{ json_encode($mapCenter) }}"
         data-points="{{ json_encode($mapPoints) }}"></div>
</div>
