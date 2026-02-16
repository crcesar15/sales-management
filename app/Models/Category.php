<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'pivot',
    ];

    /** @return BelongsToMany<Product, $this, Pivot>*/
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
