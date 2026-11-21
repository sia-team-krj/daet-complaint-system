<?php

namespace App\Services;

use App\Models\Department;

/**
 * The routing facade used by intake and the public labels.
 *
 * The mapping lives in the database now (`complaint_categories` points at a
 * receiving office), so this class is intentionally thin: it keeps the old
 * call sites stable while delegating to ComplaintTaxonomy. Archiving a category
 * or an office takes effect immediately; no config file has to agree.
 */
class DepartmentRouter
{
    /**
     * The office that catches a category nobody owns yet, and the fallback
     * when the mapped office is missing or archived. The Mayor's Office is
     * the general desk: every escalated concern lands there.
     */
    public const FALLBACK_CODE = 'OM';

    /**
     * Resolve category to Department model.
     *
     * Archived offices are skipped. Archiving is the admin's stated signal that
     * an office should stop receiving new work, so resolving to one would make
     * the whole archive feature decorative. The taxonomy service falls back to
     * the Mayor's Office, then to any active office, so a missing mapping can
     * never drop a complaint into the unassigned pile.
     */
    public static function resolve(string $category): ?Department
    {
        return app(ComplaintTaxonomy::class)->resolve($category);
    }

    /**
     * Get department name for a category (for UX display).
     */
    public static function getDepartmentName(string $category): string
    {
        $department = app(ComplaintTaxonomy::class)->departmentFor($category);

        return $department['name'] ?? "Mayor's Office";
    }

    /**
     * Get all active category options grouped by the office that receives
     * them. The group key IS the office name — residents are told which
     * office handles their report, so the key is shown verbatim and must
     * match the department directory.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function getCategoryOptions(): array
    {
        return app(ComplaintTaxonomy::class)->categoryOptions();
    }

    /**
     * Every category the system still knows about, with its localized label.
     *
     * Active and archived alike, so a complaint filed before a category was
     * withdrawn still shows its own name on the public register.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return app(ComplaintTaxonomy::class)->categoryLabels();
    }

    /**
     * One category's localized label, archived categories included.
     */
    public static function categoryLabel(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return app(ComplaintTaxonomy::class)->categoryLabel($category);
    }

    /**
     * The resident picker's two-level data.
     *
     * @return list<array<string, mixed>>
     */
    public static function residentGroups(): array
    {
        return app(ComplaintTaxonomy::class)->residentGroups();
    }

    /**
     * Keys a resident may submit right now.
     *
     * @return list<string>
     */
    public static function activeCategoryKeys(): array
    {
        return app(ComplaintTaxonomy::class)->activeCategoryKeys();
    }

    /**
     * @return array{icon: string, color: string}
     */
    public static function styleFor(string $category): array
    {
        return app(ComplaintTaxonomy::class)->categoryStyle($category);
    }

    public static function intakeModeFor(?string $category): ?string
    {
        return app(ComplaintTaxonomy::class)->intakeModeFor($category);
    }
}
