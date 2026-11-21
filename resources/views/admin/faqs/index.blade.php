@extends('admin.layouts.app')

@section('title', __('admin_faqs.page.title').' - Admin')

@section('admin-content')
@php
    $creating = old('_form') === 'create';
@endphp
<div class="admin-page admin-page--directory">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('admin_faqs.page.title') }}</h1>
            <p class="admin-page-description">{{ __('admin_faqs.page.description') }}</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <div class="admin-department-filters" role="group" aria-label="{{ __('admin_faqs.filters.label') }}">
        <a href="{{ route('admin.faqs.index', ['status' => 'active']) }}"
           class="admin-filter-chip {{ $status === 'active' ? 'is-active' : '' }}"
           @if($status === 'active') aria-current="true" @endif>
            {{ __('admin_faqs.filters.active') }}
            <span class="admin-filter-chip__count">{{ $activeCount }}</span>
        </a>
        <a href="{{ route('admin.faqs.index', ['status' => 'archived']) }}"
           class="admin-filter-chip {{ $status === 'archived' ? 'is-active' : '' }}"
           @if($status === 'archived') aria-current="true" @endif>
            {{ __('admin_faqs.filters.archived') }}
            <span class="admin-filter-chip__count">{{ $archivedCount }}</span>
        </a>
        <a href="{{ route('admin.faqs.index', ['status' => 'all']) }}"
           class="admin-filter-chip {{ $status === 'all' ? 'is-active' : '' }}"
           @if($status === 'all') aria-current="true" @endif>
            {{ __('admin_faqs.filters.all') }}
            <span class="admin-filter-chip__count">{{ $totalCount }}</span>
        </a>
    </div>

    <section class="admin-panel" aria-labelledby="faq-create-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="faq-create-title" class="admin-panel-title">{{ __('admin_faqs.create.title') }}</h2>
                <p class="admin-section-description">{{ __('admin_faqs.create.description') }}</p>
            </div>
        </div>

        <form data-loading-submit method="POST" action="{{ route('admin.faqs.store') }}" class="admin-panel-body admin-form-stack">
            @csrf
            <input type="hidden" name="_form" value="create">
            <input type="hidden" name="status" value="{{ $status }}">

            <div class="admin-form-grid">
                <label class="admin-field">
                    <span>{{ __('admin_faqs.form.question_en') }}</span>
                    <input type="text" name="question_en" class="admin-input" maxlength="255" required
                           value="{{ $creating ? old('question_en') : '' }}"
                           @if($creating && $errors->has('question_en')) aria-invalid="true" @endif>
                    @if($creating && $errors->has('question_en'))<span class="admin-field-error">{{ $errors->first('question_en') }}</span>@endif
                </label>

                <label class="admin-field">
                    <span>{{ __('admin_faqs.form.question_fil') }} <small>{{ __('admin_faqs.form.optional') }}</small></span>
                    <input type="text" name="question_fil" class="admin-input" maxlength="255"
                           value="{{ $creating ? old('question_fil') : '' }}"
                           @if($creating && $errors->has('question_fil')) aria-invalid="true" @endif>
                    @if($creating && $errors->has('question_fil'))<span class="admin-field-error">{{ $errors->first('question_fil') }}</span>@endif
                </label>

                <label class="admin-field admin-field--wide">
                    <span>{{ __('admin_faqs.form.answer_en') }}</span>
                    <textarea name="answer_en" class="admin-input admin-textarea" rows="3" maxlength="5000" required
                              @if($creating && $errors->has('answer_en')) aria-invalid="true" @endif>{{ $creating ? old('answer_en') : '' }}</textarea>
                    @if($creating && $errors->has('answer_en'))<span class="admin-field-error">{{ $errors->first('answer_en') }}</span>@endif
                </label>

                <label class="admin-field admin-field--wide">
                    <span>{{ __('admin_faqs.form.answer_fil') }} <small>{{ __('admin_faqs.form.optional') }}</small></span>
                    <textarea name="answer_fil" class="admin-input admin-textarea" rows="3" maxlength="5000"
                              @if($creating && $errors->has('answer_fil')) aria-invalid="true" @endif>{{ $creating ? old('answer_fil') : '' }}</textarea>
                    @if($creating && $errors->has('answer_fil'))<span class="admin-field-error">{{ $errors->first('answer_fil') }}</span>@endif
                    <span class="admin-field-hint">{{ __('admin_faqs.form.fallback_hint') }}</span>
                </label>

                <label class="admin-field admin-field--compact">
                    <span>{{ __('admin_faqs.form.sort_order') }}</span>
                    <input type="number" name="sort_order" class="admin-input" min="0" max="9999" inputmode="numeric"
                           value="{{ $creating ? old('sort_order') : '' }}"
                           @if($creating && $errors->has('sort_order')) aria-invalid="true" @endif>
                    @if($creating && $errors->has('sort_order'))<span class="admin-field-error">{{ $errors->first('sort_order') }}</span>@endif
                    <span class="admin-field-hint">{{ __('admin_faqs.form.sort_order_hint') }}</span>
                </label>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button--primary">{{ __('admin_faqs.actions.add') }}</button>
            </div>
        </form>
    </section>

    <section class="admin-section" aria-labelledby="faq-list-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="faq-list-title" class="admin-section-title">{{ __('admin_faqs.list.title') }}</h2>
                <p class="admin-section-description">
                    @if($status === 'archived')
                        {{ __('admin_faqs.list.archived') }}
                    @elseif($status === 'all')
                        {{ __('admin_faqs.list.all') }}
                    @else
                        {{ __('admin_faqs.list.active') }}
                    @endif
                </p>
            </div>
        </div>

        @if($faqs->isEmpty())
            <div class="admin-empty-state">
                <p>{{ __('admin_faqs.empty.'.$status) }}</p>
            </div>
        @else
            <div class="admin-faq-list">
                @foreach($faqs as $faq)
                    @php
                        $editing = old('_form') === 'edit-'.$faq->id;
                    @endphp
                    <article id="faq-{{ $faq->id }}" class="admin-panel admin-faq">
                        <div class="admin-panel-heading">
                            <div>
                                <h3 class="admin-panel-title">{{ $faq->question() }}</h3>
                                <p class="admin-section-description">{{ __('admin_faqs.order.label', ['order' => $faq->sort_order]) }}</p>
                            </div>
                            <span class="admin-status {{ $faq->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">
                                {{ $faq->is_active ? __('admin_faqs.status.active') : __('admin_faqs.status.archived') }}
                            </span>
                        </div>

                        <div class="admin-panel-body admin-form-stack">
                            <p class="admin-faq-answer">{{ $faq->answer() }}</p>

                            <div class="admin-form-actions">
                                @if($faq->is_active)
                                    <form data-loading-submit method="POST" action="{{ route('admin.faqs.move', [$faq, 'up']) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="admin-button admin-button--compact admin-button--secondary"
                                                @disabled($loop->first)>
                                            {{ __('admin_faqs.actions.move_up') }}
                                        </button>
                                    </form>
                                    <form data-loading-submit method="POST" action="{{ route('admin.faqs.move', [$faq, 'down']) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="admin-button admin-button--compact admin-button--secondary"
                                                @disabled($loop->last)>
                                            {{ __('admin_faqs.actions.move_down') }}
                                        </button>
                                    </form>
                                @else
                                    <form data-loading-submit method="POST" action="{{ route('admin.faqs.toggle', $faq) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="admin-button admin-button--compact admin-button--primary">
                                            {{ __('admin_faqs.actions.restore') }}
                                        </button>
                                    </form>
                                @endif

                                <details class="admin-faq-editor" @if($editing) open @endif>
                                    <summary class="admin-button admin-button--compact admin-button--secondary">{{ __('admin_faqs.actions.edit') }}</summary>

                                    <form data-loading-submit method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="admin-form-stack">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_form" value="edit-{{ $faq->id }}">
                                        <input type="hidden" name="status" value="{{ $status }}">

                                        <div class="admin-form-grid">
                                            <label class="admin-field">
                                                <span>{{ __('admin_faqs.form.question_en') }}</span>
                                                <input type="text" name="question_en" class="admin-input" maxlength="255" required
                                                       value="{{ $editing ? old('question_en', $faq->question_en) : $faq->question_en }}"
                                                       @if($editing && $errors->has('question_en')) aria-invalid="true" @endif>
                                                @if($editing && $errors->has('question_en'))<span class="admin-field-error">{{ $errors->first('question_en') }}</span>@endif
                                            </label>

                                            <label class="admin-field">
                                                <span>{{ __('admin_faqs.form.question_fil') }} <small>{{ __('admin_faqs.form.optional') }}</small></span>
                                                <input type="text" name="question_fil" class="admin-input" maxlength="255"
                                                       value="{{ $editing ? old('question_fil', $faq->question_fil) : $faq->question_fil }}"
                                                       @if($editing && $errors->has('question_fil')) aria-invalid="true" @endif>
                                                @if($editing && $errors->has('question_fil'))<span class="admin-field-error">{{ $errors->first('question_fil') }}</span>@endif
                                            </label>

                                            <label class="admin-field admin-field--wide">
                                                <span>{{ __('admin_faqs.form.answer_en') }}</span>
                                                <textarea name="answer_en" class="admin-input admin-textarea" rows="3" maxlength="5000" required
                                                          @if($editing && $errors->has('answer_en')) aria-invalid="true" @endif>{{ $editing ? old('answer_en', $faq->answer_en) : $faq->answer_en }}</textarea>
                                                @if($editing && $errors->has('answer_en'))<span class="admin-field-error">{{ $errors->first('answer_en') }}</span>@endif
                                            </label>

                                            <label class="admin-field admin-field--wide">
                                                <span>{{ __('admin_faqs.form.answer_fil') }} <small>{{ __('admin_faqs.form.optional') }}</small></span>
                                                <textarea name="answer_fil" class="admin-input admin-textarea" rows="3" maxlength="5000"
                                                          @if($editing && $errors->has('answer_fil')) aria-invalid="true" @endif>{{ $editing ? old('answer_fil', $faq->answer_fil) : $faq->answer_fil }}</textarea>
                                                @if($editing && $errors->has('answer_fil'))<span class="admin-field-error">{{ $errors->first('answer_fil') }}</span>@endif
                                                <span class="admin-field-hint">{{ __('admin_faqs.form.fallback_hint') }}</span>
                                            </label>

                                            <label class="admin-field admin-field--compact">
                                                <span>{{ __('admin_faqs.form.sort_order') }}</span>
                                                <input type="number" name="sort_order" class="admin-input" min="0" max="9999" inputmode="numeric"
                                                       value="{{ $editing ? old('sort_order', $faq->sort_order) : $faq->sort_order }}"
                                                       @if($editing && $errors->has('sort_order')) aria-invalid="true" @endif>
                                                @if($editing && $errors->has('sort_order'))<span class="admin-field-error">{{ $errors->first('sort_order') }}</span>@endif
                                            </label>
                                        </div>

                                        <div class="admin-form-actions">
                                            <button type="submit" class="admin-button admin-button--primary admin-button--compact">{{ __('admin_faqs.actions.save') }}</button>
                                        </div>
                                    </form>
                                </details>

                                @if($faq->is_active)
                                    <details class="admin-archive">
                                        <summary class="admin-button admin-button--compact admin-button--danger-ghost">{{ __('admin_faqs.actions.archive') }}</summary>
                                        <div class="admin-archive__confirm">
                                            <p>
                                                {{ __('admin_faqs.archive.confirm_lead') }}
                                                <strong>{{ $faq->question() }}</strong>
                                                {{ __('admin_faqs.archive.confirm_tail') }}
                                            </p>
                                            <div class="admin-form-actions">
                                                <form data-loading-submit method="POST" action="{{ route('admin.faqs.toggle', $faq) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $status }}">
                                                    <button type="submit" class="admin-button admin-button--compact admin-button--danger-ghost">
                                                        {{ __('admin_faqs.archive.confirm_button') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                @endif

                                <details class="admin-archive">
                                    <summary class="admin-button admin-button--compact admin-button--danger-ghost">{{ __('admin_faqs.actions.delete') }}</summary>
                                    <div class="admin-archive__confirm">
                                        <p>
                                            {{ __('admin_faqs.delete.confirm_lead') }}
                                            <strong>{{ $faq->question() }}</strong>.
                                            {{ __('admin_faqs.delete.confirm_tail') }}
                                        </p>
                                        <div class="admin-form-actions">
                                            <form data-loading-submit method="POST" action="{{ route('admin.faqs.destroy', $faq) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button type="submit" class="admin-button admin-button--compact admin-button--danger-ghost">
                                                    {{ __('admin_faqs.delete.confirm_button') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const highlighted = @json(session('highlight'));
        if (highlighted) {
            const target = document.getElementById(highlighted);
            if (target) {
                target.classList.add('is-highlighted');
                target.scrollIntoView({ block: 'center' });
            }
        }
    })();
</script>
@endpush
