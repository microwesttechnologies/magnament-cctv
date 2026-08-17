<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class ServiceOrderEvidence extends Model
{
    protected $table = 'service_order_evidences_tb';

    protected $fillable = [
        'service_order_id',
        'uploaded_by',
        'staff_id',
        'path',
        'original_name',
        'mime',
        'description',
    ];

    /** @return BelongsTo<ServiceOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function deleteFile(): void
    {
        if ($this->path !== '' && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
