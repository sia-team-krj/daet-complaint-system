<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * The two standing legal documents: the Privacy Policy and the Terms and
 * Conditions.
 *
 * A dedicated controller rather than closures in the route file because both
 * pages are plain Blade and need nothing from the database. If either page ever
 * needs to state a value that changes — a retention period, a contact address —
 * this is the one place to change it.
 */
class LegalController extends Controller
{
    public function privacyPolicy(): View
    {
        return view('pages.legal.privacy-policy');
    }

    public function termsAndConditions(): View
    {
        return view('pages.legal.terms');
    }
}