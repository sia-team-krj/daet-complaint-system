<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One piece of proof: a photograph of the work, with where and when it was
 * taken.
 *
 * A complaint log carries the list of photo paths; this carries what each
 * photograph *is*. The location is the point — a photograph of a repair is
 * evidence that the crew was at the reported site, and that only means
 * something if the place is recorded.
 */
class ComplaintEvidence extends Model
{
    // "Evidence" is uncountable to Laravel's inflector, which would guess
    // `complaint_evidence`. The portal's tables are plural.
    protected $table = 'complaint_evidences';

    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'log_id',
        'stage',
        'path',
        'caption',
        'latitude',
        'longitude',
        'location_source',
        'captured_at',
        'uploaded_by',
        'created_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ComplaintLog::class, 'log_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Whether this photograph carries a place.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * The location as it is shown to a person.
     */
    public function locationLabel(): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return number_format((float) $this->latitude, 6).', '.number_format((float) $this->longitude, 6);
    }
}
