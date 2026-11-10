<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Soft navigation: the endpoints that answer with rendered fragments.
 *
 * A fragment endpoint is a machine endpoint. Typed into the address bar it
 * used to answer with raw JSON — a public page should never greet a visitor
 * with a wall of braces, and a crawler should never index one. So the endpoint
 * only answers a script (a JSON request, or an XHR); anything else is sent to
 * the page it belongs to, with the same state in the URL.
 */
trait SoftNavigation
{
    /**
     * Is this a script asking for fragments?
     */
    protected function wantsFragments(Request $request): bool
    {
        return $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * The page this endpoint belongs to, with the state it was asked about.
     *
     * @return string
     */
    protected function fragmentFallbackUrl(Request $request, string $pageRoute): string
    {
        $query = array_filter(
            $request->query(),
            fn ($value) => $value !== null && $value !== '',
        );

        return route($pageRoute, $query);
    }
}
