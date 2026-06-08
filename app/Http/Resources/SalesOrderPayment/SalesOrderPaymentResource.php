<?php

declare(strict_types=1);

namespace App\Http\Resources\SalesOrderPayment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SalesOrderPayment */
final class SalesOrderPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method' => $this->payment_method->value,
            'amount' => (float) $this->amount,
            'reference' => $this->reference,
        ];
    }
}
