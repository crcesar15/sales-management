<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'model_type',
        'filename',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function product()
    {
        return $this->morphTo();
    }
}
