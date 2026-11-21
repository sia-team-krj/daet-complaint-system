{{--
    Category line icon inside a tinted circle.

    The shape and tint now come from the database-backed taxonomy, keyed by the
    stable category key — never by the translated label. That means an admin who
    renames a category, recolours it, or archives it still gets the right icon on
    public history, and a brand-new category is styled the moment it is created.
--}}
@php
    $style = \App\Services\DepartmentRouter::styleFor((string) $category);
@endphp
<span class="cat-icon cat-icon--{{ $style['color'] }}" aria-hidden="true">
    @include('complaints.partials.tile-icon', ['icon' => $style['icon']])
</span>
