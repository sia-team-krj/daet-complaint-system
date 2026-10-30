{{--
    Notification icon. Authored SVG in one consistent stroke weight - never a
    unicode glyph standing in for an icon set. Shared by the navbar feed and the
    full page so the two cannot drift apart.
--}}
@php
    $icons = [
        'verify' => '<path d="M9 12l2 2 4-4"></path><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path>',
        'handover' => '<path d="M4 8h11l-3-3"></path><path d="M20 16H9l3 3"></path>',
        'question' => '<circle cx="12" cy="12" r="9"></circle><path d="M9.5 9.5a2.5 2.5 0 1 1 3.4 2.3c-.6.3-.9.8-.9 1.4v.3"></path><path d="M12 17.5v.01"></path>',
        'reply' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12z"></path><path d="M9 11.5h6"></path><path d="M9 14.5h3.5"></path>',
        'waiting' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'status' => '<path d="M20 6 9 17l-5-5"></path>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.7 21a2 2 0 0 1-3.4 0"></path>',
    ];
    $paths = $icons[$iconKey] ?? $icons['bell'];
@endphp
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
