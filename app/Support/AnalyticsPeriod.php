<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The window a department analytics page is looking at.
 *
 * One object owns the presets, the custom range, the previous period and the
 * bucket size of the trend. The page, the fragment endpoint, the CSV and the
 * print view all build it from the same query string, so they can never
 * disagree about what "this month" means.
 *
 * The URL is the truth: `?period=month|7d|30d|90d|ytd|custom&from=Y-m-d&to=Y-m-d`.
 * An invalid custom range falls back to the default rather than throwing a 500
 * at a person who typed a date.
 */
final class AnalyticsPeriod
{
    public const DEFAULT = 'month';

    public const PRESETS = ['today', 'month', '7d', '30d', '90d', 'ytd', 'custom'];

    public function __construct(
        public readonly string $preset,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->query('period', self::DEFAULT);

        if (! in_array($preset, self::PRESETS, true)) {
            $preset = self::DEFAULT;
        }

        $today = self::today();

        if ($preset !== 'custom') {
            return self::preset($preset, $today);
        }

        $from = self::parseDate($request->query('from'));
        $to = self::parseDate($request->query('to'));

        // A custom range that cannot be read, or that runs backwards, is not a
        // reason to fail: show the default and let the reader try again.
        if ($from === null || $to === null || $from->gt($to)) {
            return self::preset(self::DEFAULT, $today);
        }

        $start = $from->startOfDay();
        $end = $to->endOfDay()->min($today->endOfDay());

        if ($start->gt($end)) {
            return self::preset(self::DEFAULT, $today);
        }

        return new self('custom', $start, $end);
    }

    public static function preset(string $preset, ?CarbonImmutable $today = null): self
    {
        $today ??= self::today();

        [$start, $end] = match ($preset) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            '30d' => [$today->subDays(29), $today],
            '90d' => [$today->subDays(89), $today],
            'ytd' => [$today->startOfYear(), $today],
            default => [$today->startOfMonth(), $today],
        };

        return new self(
            $preset === 'custom' ? self::DEFAULT : $preset,
            $start->startOfDay(),
            $end->endOfDay(),
        );
    }

    /**
     * The period this one is compared with.
     *
     * "This month" compares with the previous calendar month, and "year to
     * date" with the same span last year — the comparisons a reader expects.
     * A rolling or custom range compares with the immediately preceding span
     * of the same length, so the two windows are the same size.
     */
    public function previous(): self
    {
        if ($this->preset === 'month') {
            $start = $this->start->subMonthNoOverflow()->startOfMonth();
            $end = $start->endOfMonth()->endOfDay();

            return new self('previous', $start, $end);
        }

        if ($this->preset === 'ytd') {
            return new self(
                'previous',
                $this->start->subYear()->startOfDay(),
                $this->end->subYear()->endOfDay(),
            );
        }

        $end = $this->start->subSecond();

        return new self(
            'previous',
            $end->subDays($this->days() - 1)->startOfDay(),
            $end->endOfDay(),
        );
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    /**
     * The query string that reproduces this period.
     *
     * Only a custom range carries dates; a preset is a name, not a pair of
     * frozen dates, so "this month" stays this month when the link is shared
     * tomorrow.
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        if ($this->preset !== 'custom') {
            return ['period' => $this->preset];
        }

        return [
            'period' => 'custom',
            'from' => $this->start->toDateString(),
            'to' => $this->end->toDateString(),
        ];
    }

    public function granularity(): string
    {
        return match (true) {
            $this->days() <= 31 => 'daily',
            $this->days() <= 120 => 'weekly',
            default => 'monthly',
        };
    }

    /**
     * The trend buckets: a start, an end and a display label.
     *
     * Daily for a month or less, weekly up to four months, monthly beyond —
     * a year of daily points is a scribble, not a trend.
     *
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable, label: string}>
     */
    public function buckets(): array
    {
        $granularity = $this->granularity();
        $format = $granularity === 'monthly' ? 'M Y' : 'M j';
        $buckets = [];

        $cursor = $this->start;

        while ($cursor->lte($this->end)) {
            $bucketEnd = match ($granularity) {
                'weekly' => $cursor->addDays(6)->endOfDay(),
                'monthly' => $cursor->endOfMonth()->endOfDay(),
                default => $cursor->endOfDay(),
            };

            if ($bucketEnd->gt($this->end)) {
                $bucketEnd = $this->end;
            }

            $buckets[] = [
                'start' => $cursor,
                'end' => $bucketEnd,
                'label' => $cursor->locale(app()->getLocale())->translatedFormat($format),
            ];

            $cursor = match ($granularity) {
                'weekly' => $cursor->addDays(7),
                'monthly' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    /**
     * The human range, in the current locale.
     */
    public function rangeLabel(): string
    {
        return __('analytics.period.range', [
            'from' => $this->start->locale(app()->getLocale())->translatedFormat('M j, Y'),
            'to' => $this->end->locale(app()->getLocale())->translatedFormat('M j, Y'),
        ]);
    }

    public function presetLabel(): string
    {
        return __('analytics.period.'.$this->preset);
    }

    private static function today(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('app.timezone', 'Asia/Manila'))->startOfDay();
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', trim($value), (string) config('app.timezone', 'Asia/Manila'));
        } catch (\Throwable) {
            return null;
        }

        // createFromFormat is lenient about overflow (2026-02-31 becomes
        // 2026-03-03); reject anything that does not round-trip so an
        // impossible date cannot silently shift the report window.
        if ($date === null || $date->format('Y-m-d') !== trim($value)) {
            return null;
        }

        return $date->startOfDay();
    }
}
