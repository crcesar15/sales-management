<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;
}
