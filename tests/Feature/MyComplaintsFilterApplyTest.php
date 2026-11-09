<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MyComplaintsFilterApplyTest extends TestCase
{
    public function test_filter_selects_are_drafts_and_apply_is_an_explicit_submit(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'locale' => 'en']);

        $html = $this->actingAs($user)
            ->get(route('complaints.index'))
            ->assertOk()
            ->getContent();

        // Draft-then-Apply: no select auto-submits on change.
        $this->assertStringNotContainsString('onchange', $html);

        // Apply is an explicit submit inside the filter form.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*class="mc-filters-apply"/',
            $html
        );

        // The Clear-filters link returns to the bare index.
        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*\/complaints" class="mc-filters-clear"/',
            $html
        );

        // The popover contract ships in the page: toggle + hidden panel.
        $this->assertMatchesRegularExpression('/id="mc-filters-toggle"/', $html);
        $this->assertMatchesRegularExpression('/id="mc-filters-panel"/', $html);
    }
}
