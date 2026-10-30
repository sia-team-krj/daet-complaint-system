<?php

namespace App\Livewire;

use App\Services\BarangayLocator;
use App\Services\TransparencyFeed;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The public transparency register, kept live.
 *
 * This page used to be a controller render: one query, one finished HTML
 * document, and nothing on it that could ask the database anything again. A
 * complaint filed, or one switched to private, changed server-side state that
 * nobody was listening for, so the register kept showing a snapshot until the
 * reader reloaded it by hand. For a page whose entire claim is "this is what
 * the public can see right now", that was the wrong behaviour to have.
 *
 * Everything derived from the register query re-renders together — the rows,
 * the heat map, the public count, the "Updated" line, and the filter options —
 * because a register that refreshes its rows while its own map and totals go
 * stale is worse than one that never refreshed at all.
 *
 * The search and filter controls remain client-side. They always were: they
 * filter an already-rendered list and never went to the server. Livewire now
 * owns the elements they are bound to, so the page re-binds them after every
 * update and restores whatever the reader had typed. See the transparency
 * script in the page view.
 */
class TransparencyRegister extends Component
{
    /**
     * How often the register re-reads the database, in seconds.
     *
     * Twenty is the figure this page should run on, and the reasoning is worth
     * more than the number.
     *
     * The page is public and may be open by anyone, including crawlers, so this
     * interval is a direct multiplier on server load — and the register query is
     * unbounded: it reads every public complaint, with no limit. At twenty
     * seconds a tab left open costs three full table reads a minute, which is a
     * cost a public page can carry. At five it costs twelve times that, and at
     * two, sixty.
     *
     * Twenty is also short enough that a complaint someone has just filed shows
     * up while they are still reading the page, so the safety costs nothing you
     * can feel. The number exists to stop that trade being made by accident.
     *
     * Two of Livewire's own poll safeguards bound it further, and it is worth
     * being precise about which does what: a backgrounded tab is throttled by
     * Livewire regardless of what we ask for, and the `.visible` modifier
     * additionally pauses the poll once the reader has scrolled the register off
     * screen. So the cost tracks attention rather than open tabs.
     *
     * Change it in one place: this constant. A test asserts it stays safe.
     */
    public const REFRESH_SECONDS = 20;

    public function render(TransparencyFeed $feed, BarangayLocator $barangays): View
    {
        return view('livewire.transparency-register', $feed->indexPayload($barangays));
    }
}