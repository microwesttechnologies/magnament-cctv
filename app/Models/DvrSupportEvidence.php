<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DvrSupportEvidence extends Model
{
    protected $table = 'dvr_support_evidences_tb';

    protected $fillable = [
        'dvr_support_id',
        'path',
        'original_name',
    ];

    /**
     * @return BelongsTo<DvrSupport, $this>
     */
    public function support(): BelongsTo
    {
        return $this->belongsTo(DvrSupport::class, 'dvr_support_id');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function deleteFile(): void
    {
        if ($this->path && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
