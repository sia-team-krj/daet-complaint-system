<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintCategory extends Model
{
    protected $fillable = ['key', 'label_en', 'label_fil', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function label(): string
    {
        return app()->getLocale() === 'fil' && $this->label_fil
            ? $this->label_fil
            : $this->label_en;
    }
}
