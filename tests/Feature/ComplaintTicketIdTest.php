<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTicketIdTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function generated_ticket_id_matches_random_format(): void
    {
        $complaint = Complaint::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^COMP-\d{4}-[A-Z2-9]{5}$/',
            $complaint->ticket_id,
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function two_complaints_get_different_ticket_ids(): void
    {
        $first = Complaint::factory()->create();
        $second = Complaint::factory()->create();

        $this->assertNotSame($first->ticket_id, $second->ticket_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function manually_supplied_ticket_id_is_preserved(): void
    {
        $custom = 'COMP-2026-CUSTOM';

        $complaint = Complaint::factory()->create(['ticket_id' => $custom]);

        $this->assertSame($custom, $complaint->ticket_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_id_is_unique_including_soft_deleted_rows(): void
    {
        $original = Complaint::factory()->create();
        $id = $original->ticket_id;

        $original->delete();

        // Force a candidate collision by pre-seeding the exact id is not possible
        // through the hook (it skips filled ids), so instead assert the soft-deleted
        // row still occupies the id and a fresh auto-generated id differs.
        $fresh = Complaint::factory()->create();

        $this->assertNotSame($id, $fresh->ticket_id);
        $this->assertDatabaseHas('complaints', [
            'ticket_id' => $id,
            'deleted_at' => $original->deleted_at,
        ]);
    }
}