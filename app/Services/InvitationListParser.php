<?php

namespace App\Services;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns a pasted recipient list into rows an admin can review before any mail
 * leaves the system.
 *
 * Accepted line shapes, one recipient per line:
 *   juan@daet.gov.ph
 *   juan@daet.gov.ph | Engineering
 *   Juan Dela Cruz <juan@daet.gov.ph>
 *   Juan Dela Cruz <juan@daet.gov.ph> | ENG
 *
 * Blank lines and lines starting with # are ignored. The pipe separator and the
 * angle-bracket form are both optional.
 */
class InvitationListParser
{
    public const READY = 'ready';

    public const SKIPPED = 'skipped';

    public const INVALID = 'invalid';

    public function __construct(
        private readonly int $maxRecipients = 25,
    ) {}

    /**
     * @param  Collection<int, Department>  $departments  Active departments available for selection.
     * @return array{rows: Collection<int, array<string, mixed>>, ready: int, skipped: int, invalid: int, total: int}
     */
    public function parse(string $raw, ?Department $defaultDepartment = null, ?Collection $departments = null): array
    {
        $departments ??= Department::where('is_active', true)->orderBy('name')->get();

        $lookup = $this->departmentLookup($departments);
        $existingEmails = $this->existingAccountEmails();
        $openInvitations = $this->openInvitationEmails();

        $rows = collect();
        $seenInBatch = [];

        foreach (preg_split('/\R/u', $raw) ?: [] as $index => $line) {
            $lineNumber = $index + 1;
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if ($rows->count() >= $this->maxRecipients) {
                $rows->push($this->row($lineNumber, $line, null, null, null, self::INVALID, "Over the {$this->maxRecipients} recipient limit."));

                break;
            }

            $parsed = $this->parseLine($line);

            if ($parsed['email'] === null) {
                $rows->push($this->row($lineNumber, $line, $parsed['name'], null, null, self::INVALID, 'No email address found on this line.'));

                continue;
            }

            $email = $parsed['email'];

            if (isset($seenInBatch[$email])) {
                $rows->push($this->row($lineNumber, $line, $parsed['name'], $email, null, self::SKIPPED, "Repeated in this list (first on line {$seenInBatch[$email]})."));

                continue;
            }

            $department = $parsed['department'] !== null
                ? ($lookup[Str::lower($parsed['department'])] ?? null)
                : $defaultDepartment;

            if ($department === null) {
                $reason = $parsed['department'] !== null
                    ? "Unknown department \"{$parsed['department']}\"."
                    : 'No department. Pick one above or add " | Department" to this line.';

                $rows->push($this->row($lineNumber, $line, $parsed['name'], $email, null, self::INVALID, $reason));

                continue;
            }

            if (isset($existingEmails[$email])) {
                $rows->push($this->row($lineNumber, $line, $parsed['name'], $email, $department, self::SKIPPED, 'Already has an account.'));

                continue;
            }

            if (isset($openInvitations[$email])) {
                $rows->push($this->row($lineNumber, $line, $parsed['name'], $email, $department, self::SKIPPED, 'Already has an open invitation.'));

                continue;
            }

            $seenInBatch[$email] = $lineNumber;
            $rows->push($this->row($lineNumber, $line, $parsed['name'], $email, $department, self::READY, null));
        }

        return [
            'rows' => $rows,
            'ready' => $rows->where('status', self::READY)->count(),
            'skipped' => $rows->where('status', self::SKIPPED)->count(),
            'invalid' => $rows->where('status', self::INVALID)->count(),
            'total' => $rows->count(),
        ];
    }

    /**
     * @return array{name: ?string, email: ?string, department: ?string}
     */
    private function parseLine(string $line): array
    {
        $name = null;
        $department = null;

        // Optional trailing "| Department".
        if (str_contains($line, '|')) {
            [$line, $department] = array_pad(explode('|', $line, 2), 2, null);
            $line = trim($line);
            $department = $department !== null ? trim($department) : null;
            $department = $department === '' ? null : $department;
        }

        // Optional "Display Name <email>".
        if (preg_match('/^(.*?)<\s*([^>]+?)\s*>$/u', $line, $matches) === 1) {
            $name = trim($matches[1]) ?: null;
            $line = trim($matches[2]);
        }

        $candidate = trim((string) Str::of($line)->before(' ')->lower()->value());

        if ($name === null && str_contains($line, ' ')) {
            $name = trim(Str::before($line, $candidate)) ?: null;
        }

        $email = filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : null;

        return [
            'name' => $name,
            'email' => $email,
            'department' => $department,
        ];
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return array<string, Department>
     */
    private function departmentLookup(Collection $departments): array
    {
        return $departments
            ->flatMap(fn (Department $department) => [
                Str::lower($department->name) => $department,
                Str::lower((string) $department->code) => $department,
            ])
            ->all();
    }

    /**
     * @return array<string, true>
     */
    private function existingAccountEmails(): array
    {
        return User::query()
            ->pluck('email')
            ->mapWithKeys(fn (string $email) => [Str::lower($email) => true])
            ->all();
    }

    /**
     * @return array<string, true>
     */
    private function openInvitationEmails(): array
    {
        return InvitationCode::query()
            ->where('is_active', true)
            ->where('used_count', 0)
            ->where('expires_at', '>', now())
            ->whereNotNull('invitee_email')
            ->pluck('invitee_email')
            ->mapWithKeys(fn (string $email) => [Str::lower($email) => true])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        int $line,
        string $raw,
        ?string $name,
        ?string $email,
        ?Department $department,
        string $status,
        ?string $reason,
    ): array {
        return [
            'line' => $line,
            'raw' => $raw,
            'name' => $name,
            'email' => $email,
            'department_id' => $department?->id,
            'department_name' => $department?->name,
            'status' => $status,
            'reason' => $reason,
        ];
    }
}
