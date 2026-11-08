@extends($mainLayout)

@section('title', __('transparency.index.title'))

@section('content')
    {{-- The transparency register is a Livewire component so it can re-read the
         database on a timer. All visible markup lives inside the component view
         (livewire/transparency-register.blade.php); this page only provides the
         shell and the script stack. --}}
    <livewire:transparency-register />

    {{-- The render is owned by the component; the shell layout must not add a
         second footer. --}}
    @section('site-footer')
        <span hidden aria-hidden="true"></span>
    @endsection
@endsection

@push('scripts')
    @include('pages.transparency.partials.register-script')
@endpush
