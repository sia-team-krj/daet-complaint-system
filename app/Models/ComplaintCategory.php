<?php

namespace App\Models;

use App\Services\ComplaintTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One selectable complaint category.
 *
 * `key` is the stable value a complaint stores. Labels are editable; the key
 * is generated once from the English label and never changes, so renaming a
 * category cannot orphan the complaints already filed under it.
 */
class ComplaintCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group_id',
        'department_id',
        'label_en',
        'label_fil',
        'helper_en',
        'helper_fil',
        'icon',
        'color',
        'intake_mode',
        'is_active',
        'sort_order',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategoryGroup::class, 'group_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Complaints filed under this category, including archived ones.
     * The foreign key is the stable string key, not the row id.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'category', 'key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDepartment($query, Department|int $department)
    {
        return $query->where('department_id', $department instanceof Department ? $department->getKey() : $department);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label_en');
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

    /**
     * A category is offered only when it and its group are active.
     */
    public function isOffered(): bool
    {
        return $this->is_active && $this->group?->is_active;
    }
}
