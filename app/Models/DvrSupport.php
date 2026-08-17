<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DvrSupport extends Model
{
    protected $table = 'dvr_supports_tb';

    protected $fillable = [
        'dvr_id',
        'staff_id',
        'title',
        'description',
    ];

    /**
     * @return BelongsTo<Dvr, $this>
     */
    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class, 'dvr_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * @return HasMany<DvrSupportEvidence, $this>
     */
    public function evidences(): HasMany
    {
        return $this->hasMany(DvrSupportEvidence::class, 'dvr_support_id');
    }
}
