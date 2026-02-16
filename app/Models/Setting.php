<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Setting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'value',
        'name',
        'group',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('setting')
            ->dontSubmitEmptyLogs();
    }
}
