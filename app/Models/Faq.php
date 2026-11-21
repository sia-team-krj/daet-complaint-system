<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A question residents ask before they file, managed by the super admin.
 *
 * English is the required copy. Filipino is optional: when it is blank the
 * public page falls back to English, so a half-translated FAQ never shows an
 * empty answer.
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_en',
        'question_fil',
        'answer_en',
        'answer_fil',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Published rows, in the order the landing page renders them. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** The one display order, used by both the admin list and the public page. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function question(): string
    {
        return $this->localized('question');
    }

    public function answer(): string
    {
        return $this->localized('answer');
    }

    /**
     * Filipino when it is filled, English otherwise. The controller trims
     * empty optional fields to null, so `filled()` is enough here.
     */
    private function localized(string $field): string
    {
        $filipino = $this->{$field.'_fil'};

        if (app()->getLocale() === 'fil' && filled($filipino)) {
            return (string) $filipino;
        }

        return (string) $this->{$field.'_en'};
    }
}
