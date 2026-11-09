@extends('admin.layouts.app')

@section('title', 'Create Department - Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.departments.index') }}">Departments</a>
        <span aria-hidden="true">/</span>
        <span>Create department</span>
    </nav>

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Create department</h1>
            <p class="admin-page-description">
                A new office can receive complaints, hold staff accounts, and issue invitations.
                It starts active and can be archived later without losing anything.
            </p>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">Office details</h2>
        </div>

        <form data-loading-submit method="POST" action="{{ route('admin.departments.store') }}" class="admin-panel-body admin-form-stack">
            @csrf

            <label class="admin-field">
                <span>Office name</span>
                <input type="text" name="name" class="admin-input" value="{{ old('name') }}" required maxlength="100"
                       placeholder="Office of the Mayor" autofocus>
                @error('name')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>

            <label class="admin-field">
                <span>Office code</span>
                <input type="text" name="code" class="admin-input" value="{{ old('code') }}" required maxlength="10"
                       pattern="[A-Z0-9]+" autocapitalize="characters" spellcheck="false" placeholder="OM">
                @error('code')<span class="admin-field-error">{{ $message }}</span>@enderror
                <span class="admin-field-hint">
                    Up to 10 uppercase letters or digits. Must not already be in use - in use:
                    {{ $existingCodes->implode(', ') }}.
                </span>
            </label>

            <label class="admin-field">
                <span>Description</span>
                <textarea name="description" class="admin-input admin-textarea" rows="4" maxlength="255"
                          placeholder="What this office handles, so staff know when a complaint belongs here.">{{ old('description') }}</textarea>
                @error('description')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button--primary">Create department</button>
                <a href="{{ route('admin.departments.index') }}" class="admin-button admin-button--secondary">Cancel</a>
            </div>
        </form>
    </section>

    {{-- Stated here because this is where the code is chosen, and it is the one
         thing an admin cannot discover afterwards. Five codes auto-route
         complaints; every other office receives nothing automatically and has to
         be fed by hand. Finding that out weeks later, when the new office sits
         empty, is the failure this paragraph prevents. --}}
    <section class="admin-panel admin-department-routing-note">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">What happens to the code you choose</h2>
        </div>
        <div class="admin-panel-body">
            <p>
                Complaints are routed automatically for
                <strong>{{ count($routableCodes) }} codes</strong> -
                {{ implode(', ', $routableCodes) }}. Those are the codes residents' complaint
                categories are mapped to.
            </p>
            <p>
                Any other code creates an office that receives nothing on its own. It can still hold
                staff, still issue invitations, and can be assigned complaints by hand - but the
                router will not send work to it.
            </p>
            <p>
                A code can be changed at any time while the office is not routing automatically.
                Once an office is archived, creating a replacement with the code you want is the way
                to give it back.
            </p>
        </div>
    </section>
</div>
@endsection