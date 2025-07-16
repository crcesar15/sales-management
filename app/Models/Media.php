<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'model_type',
        'filename',
        'meta',
    ];

    // add url attribute
    protected $appends = ['url'];

    public function product()
    {
        return $this->morphTo();
    }

    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => Storage::url('products/' . $this->filename));
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}
