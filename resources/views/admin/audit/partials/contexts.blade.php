{{--
    The scope rail: everyone, the residents, every office, the records with no
    actor. One row per scope, each with the number of people behind it.
--}}
                    <p class="admin-audit-browser__rail-head">
                        <span class="admin-audit-browser__rail-head-label">{{ __('audit.scope') }}</span>
                        <span class="admin-audit-browser__rail-head-label">{{ __('audit.people') }}</span>
                    </p>

                    <a href="{{ route('admin.audit.index') }}"
                       class="admin-audit-browser__context{{ ! $selection['active'] ? ' is-selected' : '' }}"
                       @if(! $selection['active']) aria-current="true" @endif>
                        <span class="admin-audit-browser__context-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <span class="admin-audit-browser__context-name">{{ __('audit.everyone') }}</span>
                        <span class="admin-audit-browser__context-count">{{ $directory['people'] }}<span class="sr-only"> {{ trans_choice('audit.people_unit', $directory['people']) }}</span></span>
                    </a>

                    <p class="admin-audit-browser__label">{{ __('audit.residents') }}</p>
                    <a href="{{ route('admin.audit.index', array_merge($auditQuery, ['residents' => 1])) }}"
                       class="admin-audit-browser__context{{ $residentsOnly ? ' is-selected' : '' }}"
                       @if($residentsOnly) aria-current="true" @endif>
                        <span class="admin-audit-browser__context-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <span class="admin-audit-browser__context-name">{{ __('audit.residents_all') }}</span>
                        <span class="admin-audit-browser__context-count">{{ $directory['residents_people'] }}<span class="sr-only"> {{ trans_choice('audit.people_unit', $directory['residents_people']) }}</span></span>
                    </a>

                    <p class="admin-audit-browser__label">{{ __('audit.departments') }}</p>
                    @foreach($directory['offices'] as $office)
                        <a href="{{ route('admin.audit.index', array_merge($auditQuery, ['department_id' => $office['id']])) }}"
                           class="admin-audit-browser__context{{ $departmentId === $office['id'] ? ' is-selected' : '' }}"
                           title="{{ $office['name'] }}"
                           @if($departmentId === $office['id']) aria-current="true" @endif>
                            <span class="dept-badge" data-dept="{{ $office['code'] }}">{{ $office['code'] }}</span>
                            <span class="admin-audit-browser__context-name">{{ $office['name'] }}</span>
                            <span class="admin-audit-browser__context-count">{{ $office['people'] }}<span class="sr-only"> {{ trans_choice('audit.people_unit', $office['people']) }}</span></span>
                        </a>
                    @endforeach

                    <p class="admin-audit-browser__label">{{ __('audit.automated') }}</p>
                    <a href="{{ route('admin.audit.index', array_merge($auditQuery, ['system' => 1])) }}"
                       class="admin-audit-browser__context{{ $systemOnly ? ' is-selected' : '' }}"
                       @if($systemOnly) aria-current="true" @endif>
                        <span class="admin-audit-browser__context-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </span>
                        <span class="admin-audit-browser__context-name">{{ __('audit.automated_records') }}</span>
                        <span class="admin-audit-browser__context-count">{{ $directory['system'] }}<span class="sr-only"> {{ __('audit.records_unit') }}</span></span>
                    </a>

                    <p class="admin-audit-browser__hint">{{ __('audit.department_count_hint') }}</p>
