# Random Complaint Ticket IDs — Plan

**Status:** planning (not yet implemented)
**Scope:** backend only — `app/Models/Complaint.php` ticket-id generation + minor view/test/doc touch-ups.
**Decision (from user):** `COMP-YYYY-XXXXX`, 5-char uppercase alphanumeric with ambiguous glyphs removed, collision-safe via retry loop.

---

## 1. Goal

Replace the sequential, count-based ticket id

```
COMP-2026-00001
```

with a random, human-dictatable id

```
COMP-2026-A7K9P
```

so that:

- ticket numbers are not predictable / enumerable (a resident cannot guess another resident's ticket by incrementing),
- two filings in the same millisecond cannot collide on a counter race,
- the id stays short enough to read over the phone and type into `/complaints/track`.

Existing tickets (`COMP-2026-00001` …) keep their ids — no migration, no backfill.

## 2. Format contract

| Part | Value | Notes |
|---|---|---|
| Prefix | `COMP-` | unchanged brand prefix |
| Year | 4-digit `Y` of `now()` | kept for traceability / rough grouping |
| Separator | `-` | |
| Random block | exactly **5** chars | uppercase letters + digits, unambiguous alphabet |
| Full example | `COMP-2026-A7K9P` | 15 chars total, fits `ticket_id` string(30) |
| Regex | `^COMP-\d{4}-[A-Z2-9]{5}$` | used by tests + client validation |

### Unambiguous alphabet (32 chars)

```
ABCDEFGHJKLMNPQRSTUVWXYZ23456789
```

Excluded: `I`, `O`, `0`, `1` (and we also drop `S`/`5`, `B`/`8`? — see §6 open question). The 32-char set above keeps `S`, `B`, `5`, `8`; if misreading in the field turns out to be a problem, narrow to a 24-char set in one constant. **Recommendation: ship the 32-char set first**, measure support calls, then narrow if needed.

## 3. Collision handling

- Generate candidate `COMP-{year}-{5 random chars}`.
- Check `Complaint::withTrashed()->where('ticket_id', $candidate)->exists()` (soft-deleted rows count, so ids are never reused — preserves the existing guarantee in §"TICKET ID GENERATION").
- Retry up to **10** times.
- If all 10 collide, throw `RuntimeException('Unable to generate a unique ticket id')` so the filing request fails loudly instead of silently producing a duplicate (the DB unique constraint is the final backstop either way).

Expected collision probability per attempt is ~1/32⁵ ≈ 3e-8, so 10 retries is effectively infinite headroom.

## 4. Implementation

### 4.1 `app/Models/Complaint.php` — replace the `creating` hook

Current:

```php
protected static function booted(): void
{
    static::creating(function (Complaint $complaint) {
        $year = date("Y");
        $count = static::withTrashed()
            ->whereYear("created_at", $year)
            ->count();

        $complaint->ticket_id =
            "COMP-" .
            $year .
            "-" .
            str_pad($count + 1, 5, "0", STR_PAD_LEFT);
    });
}
```

New:

```php
public const TICKET_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
public const TICKET_RANDOM_LENGTH = 5;
public const TICKET_MAX_ATTEMPTS = 10;

protected static function booted(): void
{
    static::creating(function (Complaint $complaint) {
        if (filled($complaint->ticket_id)) {
            return; // allow explicit ids (seeders, tests, admin tooling)
        }

        $year = now()->format('Y');

        for ($attempt = 1; $attempt <= self::TICKET_MAX_ATTEMPTS; $attempt++) {
            $suffix = '';
            $max = strlen(self::TICKET_ALPHABET) - 1;
            for ($i = 0; $i < self::TICKET_RANDOM_LENGTH; $i++) {
                $suffix .= self::TICKET_ALPHABET[random_int(0, $max)];
            }

            $candidate = "COMP-{$year}-{$suffix}";

            if (! static::withTrashed()->where('ticket_id', $candidate)->exists()) {
                $complaint->ticket_id = $candidate;
                return;
            }
        }

        throw new \RuntimeException('Unable to generate a unique complaint ticket id.');
    });
}
```

Notes:

- `filled()` guard keeps the existing seeder/test pattern (`'ticket_id' => 'COMP-2026-...'`) working without changes.
- `random_int()` (CSPRNG) instead of `Str::random()` so the alphabet is controlled and the result is uppercase-only.
- Year is computed once per attempt loop? It's stable within a request; computing outside the loop is fine. Keep inside for clarity — negligible cost.
- No DB schema change: `ticket_id` is `string(30)` unique+indexed already.

### 4.2 Optional helper service (only if a second caller needs it)

If the admin "regenerate ticket id" or CSV import flows ever need it, extract to `app/Services/TicketIdGenerator::generate(): string`. **Not needed now** — keep logic in the model to match the existing single source of truth. Revisit only when a second caller appears.

### 4.3 View / copy touch-ups (non-blocking)

- `resources/views/complaints/track.blade.php` placeholder: `e.g. COMP-2026-00001` → `e.g. COMP-2026-A7K9P`.
- `resources/views/complaints/success.blade.php` / `show.blade.php`: no logic change; the ticket is already rendered as-is. The "save your ticket number" hint stays.
- `docs/design-system.md` §5.14 / design-system swatch: example id `COMP-2026-00022` → `COMP-2026-A7K9P` (cosmetic, optional).
- `resources/views/pages/design-system-rows.blade.php` / `design-system.blade.php`: cosmetic example ids, optional.

### 4.4 Tests

Add to an existing or new feature test (e.g. `tests/Feature/ComplaintTicketIdTest.php`):

1. A freshly created complaint's `ticket_id` matches `/^COMP-\d{4}-[A-Z2-9]{5}$/`.
2. Two complaints created in the same request get **different** ticket ids.
3. Manually supplied `ticket_id` is preserved (the `filled()` guard).
4. (Optional) creating 1,000 complaints yields no duplicates — property-style, gated behind a `--group=slow` or skipped in CI by default.

Update existing tests? They pass explicit ids, so no breakage. The only assertion that touches generation is `NotificationDeliveryTest` line 273 (`assertStringContainsString('COMP-', $notification->title)`) — still passes.

### 4.5 Documentation

- `PROJECT_CHANGES.md` / `PROGRESS.md`: note the new format + the rationale (enumeration resistance).
- `AGENT_GUIDE.md` "Database Schema" section: example becomes `COMP-YYYY-XXXXX`.

## 5. What does NOT change

- DB schema / migrations (column already `string(30)` unique+indexed).
- Search routes (`ILIKE '%ticket_id%'`) — case-insensitive already, so lowercase input still matches.
- Transparency feed, notifications, mail templates — they consume `$complaint->ticket_id` as a string.
- Soft-delete reuse guarantee — preserved via `withTrashed()`.

## 6. Open decisions (resolve before coding)

1. **Alphabet breadth** — ship 32-char (`ABCDEFGHJKLMNPQRSTUVWXYZ23456789`) or narrower 24-char (also drop `S`,`B`,`5`,`8`)? Recommendation: 32 first.
2. **Year retention** — keep `COMP-YYYY-` or drop to `COMP-XXXXXXX`? User chose keep-year; recorded here for the record.
3. **Should the random block be grouped** (e.g. `COMP-2026-A7K-9P`) for readability? Recommendation: no — keeps the regex and search simple, and 5 chars is already short.

## 7. Risks / rollback

- **Risk:** a resident who saved an old numeric ticket (`COMP-2026-00001`) is unaffected; mixed formats coexist fine.
- **Risk:** support staff trained on "incrementing the number" must switch to "read the whole id". Comms note needed.
- **Rollback:** revert the `booted()` change; new tickets created during the random era would have non-numeric suffixes but remain valid unique strings. No data migration required either way.

## 8. Effort estimate

~0.5 day: model change + 1 new test file + placeholder/cosmetic copy updates + docs.

---

**Next step if approved:** implement §4.1 and §4.3–4.4, run `php artisan test`, update `PROJECT_CHANGES.md` changelog.