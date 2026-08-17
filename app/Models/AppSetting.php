<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AppSetting extends Model
{
    protected $table = 'app_settings_tb';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];
}
