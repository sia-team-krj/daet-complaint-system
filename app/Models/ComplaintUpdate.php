<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        "complaint_id",
        "user_id",
        "user_role",
        "action",
        "message",
        "target_type",
        "target_id",
        "ip_address",
    ];

    protected function casts(): array
    {
        return [
            "created_at" => "datetime",
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
