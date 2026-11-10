{{--
    A filtered feed must not be indexed. The rule lives in the <head>, which a
    fragment swap does not touch — so the endpoint sends it along and the
    browser puts it back.
--}}
@if($anyFilter)
    <meta name="robots" content="noindex,follow">
@endif
