<?php

namespace App\Services;

use App\Models\Complaint;
use Illuminate\Support\Str;

class ComplaintDetectionService
{
    private const SPAM_TERMS = [
        'free money',
        'click here',
        'buy now',
        'limited offer',
        'guaranteed profit',
        'lottery winner',
        'crypto giveaway',
        'investment opportunity',
        'urgent wire transfer',
    ];

    /**
     * Evaluate a complaint without rejecting or deleting it.
     *
     * @return array{spam_status: string, spam_score: int, reasons: array<int, string>, duplicate_of_id: ?int, similarity_score: float}
     */
    public function evaluate(Complaint $complaint): array
    {
        $title = (string) $complaint->title;
        $description = (string) $complaint->description;
        $combined = $this->normalize($title . ' ' . $description);
        $similarityText = $this->normalize($title . ' ' . $description . ' ' . ($complaint->address_text ?? ''));
        $tokens = $this->tokens($similarityText);
        $reasons = [];
        $score = 0;

        if (preg_match('/https?:\\/\\/|www\\./i', $title . ' ' . $description)) {
            $score += 35;
            $reasons[] = 'Contains a link';
        }

        foreach (self::SPAM_TERMS as $term) {
            if (Str::contains($combined, $term)) {
                $score += 30;
                $reasons[] = 'Contains promotional or spam language';
                break;
            }
        }

        $longestRepeat = 0;
        foreach (array_count_values($tokens) as $token => $count) {
            if (strlen((string) $token) >= 4) {
                $longestRepeat = max($longestRepeat, $count);
            }
        }

        if ($longestRepeat >= 5) {
            $score += 20;
            $reasons[] = 'Repeats the same keyword unusually often';
        }

        if (mb_strlen(strip_tags($description)) < 40) {
            $score += 15;
            $reasons[] = 'Description is unusually short';
        }

        $letters = preg_replace('/[^A-Z]/', '', $title) ?? '';
        if (mb_strlen($letters) >= 12 && $letters === mb_strtoupper($letters)) {
            $score += 10;
            $reasons[] = 'Title is written entirely in capital letters';
        }

        $score = min(100, $score);
        $fingerprint = hash('sha256', $combined);
        [$duplicateId, $similarity] = $this->findSimilarity($complaint, $tokens, $fingerprint);

        $complaint->update([
            'content_fingerprint' => $fingerprint,
            'spam_status' => $score >= 60 ? 'spam' : ($score >= 25 ? 'review' : 'clear'),
            'spam_score' => $score,
            'spam_reasons' => $reasons ?: null,
            'duplicate_of_id' => $duplicateId,
            'similarity_score' => round($similarity, 2),
        ]);

        return [
            'spam_status' => $score >= 60 ? 'spam' : ($score >= 25 ? 'review' : 'clear'),
            'spam_score' => $score,
            'reasons' => $reasons,
            'duplicate_of_id' => $duplicateId,
            'similarity_score' => round($similarity, 2),
        ];
    }

    /**
     * @param array<int, string> $tokens
     * @return array{0: ?int, 1: float}
     */
    private function findSimilarity(Complaint $complaint, array $tokens, string $fingerprint): array
    {
        $exactMatch = Complaint::query()
            ->where('id', '!=', $complaint->id ?? 0)
            ->where('category', $complaint->category)
            ->where('content_fingerprint', $fingerprint)
            ->oldest('created_at')
            ->first(['id']);

        if ($exactMatch) {
            return [$exactMatch->id, 1.0];
        }

        if ($tokens === []) {
            return [null, 0.0];
        }

        $candidates = Complaint::query()
            ->where('id', '!=', $complaint->id ?? 0)
            ->where('category', $complaint->category)
            ->where('created_at', '>=', now()->subDays(90))
            ->latest('created_at')
            ->limit(100)
            ->get(['id', 'title', 'description', 'address_text']);

        $bestId = null;
        $bestScore = 0.0;
        $tokenSet = array_unique($tokens);
        $titleSet = array_unique($this->tokens($this->normalize((string) $complaint->title)));
        $address = $this->normalize((string) $complaint->address_text);

        foreach ($candidates as $candidate) {
            $candidateText = $this->normalize(
                $candidate->title . ' ' . $candidate->description . ' ' . ($candidate->address_text ?? '')
            );
            $candidateTokens = array_unique($this->tokens($candidateText));
            $union = array_unique(array_merge($tokenSet, $candidateTokens));

            if ($union === []) {
                continue;
            }

            $textScore = count(array_intersect($tokenSet, $candidateTokens)) / count($union);
            $candidateTitleSet = array_unique($this->tokens($this->normalize($candidate->title)));
            $titleUnion = array_unique(array_merge($titleSet, $candidateTitleSet));
            $titleScore = $titleUnion === []
                ? 0.0
                : count(array_intersect($titleSet, $candidateTitleSet)) / count($titleUnion);
            $addressScore = $address !== '' && $address === $this->normalize((string) $candidate->address_text) ? 1.0 : 0.0;
            $score = max($textScore, ($titleScore * 0.6) + ($addressScore * 0.4));

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $candidate->id;
            }
        }

        return [$bestScore >= 0.65 ? $bestId : null, $bestScore];
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))) ?? '');
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        $stopWords = ['about', 'after', 'again', 'from', 'have', 'into', 'that', 'their', 'there', 'these', 'they', 'this', 'with', 'would', 'your'];
        $words = preg_split('/[^\pL\pN]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_diff(array_filter($words, fn ($word) => mb_strlen($word) >= 4), $stopWords));
    }
}
