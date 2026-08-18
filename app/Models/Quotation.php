<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Quotation extends Model
{
    protected $table = 'quotations_tb';

    protected $fillable = [
        'project_id',
        'code',
        'work_description',
        'designed_solution',
        'status',
        'vat_rate_percent',
        'subtotal',
        'vat_amount',
        'total',
        'created_by',
        'signatory_user_id',
        'signatory_name',
        'signatory_phone',
        'signature_snapshot_path',
    ];

    protected function casts(): array
    {
        return [
            'vat_rate_percent' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return HasMany<QuotationLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class, 'quotation_id')->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<InstallationOrder, $this> */
    public function installationOrder(): HasOne
    {
        return $this->hasOne(InstallationOrder::class, 'quotation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
