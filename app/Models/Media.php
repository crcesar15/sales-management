<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
    ];

    //add url attribute
    protected $appends = ['url'];

    //add url attribute
    public function getUrlAttribute()
    {
        return Storage::url('products/' . $this->filename);
    }

    public function product()
    {
        return $this->morphTo();
    }
}
