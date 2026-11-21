<?php

namespace App\Models;

use App\Services\ComplaintTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A resident-facing heading in the filing wizard.
 *
 * The group is presentation and order only. Its categories carry the office
 * and intake behaviour, so archiving a group hides a whole section without
 * touching the complaints filed under it.
 */
class ComplaintCategoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label_en',
        'label_fil',
        'helper_en',
        'helper_fil',
        'icon',
        'color',
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

    protected static function booted(): void
    {
        static::saved(fn () => ComplaintTaxonomy::flush());
        static::deleted(fn () => ComplaintTaxonomy::flush());
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ComplaintCategory::class, 'group_id');
    }

    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function label(): string
    {
        return app()->getLocale() === 'fil' && filled($this->label_fil)
            ? $this->label_fil
            : $this->label_en;
    }

    public function helper(): string
    {
        return app()->getLocale() === 'fil' && filled($this->helper_fil)
            ? $this->helper_fil
            : ($this->helper_en ?? '');
    }
}
