<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class PendingMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['upload_token'];
}
