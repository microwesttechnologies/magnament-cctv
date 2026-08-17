<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PushSubscription extends Model
{
    protected $table = 'push_subscriptions_tb';

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'user_agent',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
