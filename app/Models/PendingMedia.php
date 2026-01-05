<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $upload_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereUploadToken($value)
 * @mixin \Eloquent
 */
final class PendingMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['upload_token'];
}
