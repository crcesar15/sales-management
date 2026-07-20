<?php

declare(strict_types=1);

namespace App\Http\Resources\SalesOrder;

use App\Http\Resources\CashRegisterShift\CashRegisterShiftResource;
use App\Http\Resources\SalesOrderItem\SalesOrderItemResource;
use App\Http\Resources\SalesOrderPayment\SalesOrderPaymentResource;
use App\Http\Resources\Store\StoreResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SalesOrder */
final class SalesOrderResource extends JsonResource
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
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'discount_type' => $this->discount_type->value,
            'discount_value' => (float) $this->discount_value,
            'sub_total' => (float) $this->sub_total,
            'discount' => (float) $this->discount,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'token' => $this->token,
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'cash_register_shift_id' => $this->cash_register_shift_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'display_name' => $this->customer ? trim($this->customer->first_name . ' ' . $this->customer->last_name) : null,
                'email' => $this->customer?->email,
                'first_name' => $this->customer?->first_name,
                'last_name' => $this->customer?->last_name,
                'phone' => $this->customer?->phone,
                'tax_id' => $this->customer?->tax_id,
                'tax_id_name' => $this->customer?->tax_id_name,
            ]),
            'user' => $this->whenLoaded('user', fn () => (new UserResource($this->user))->resolve()),
            'store' => $this->whenLoaded('store', fn () => (new StoreResource($this->store))->resolve()),
            'cash_register_shift' => $this->whenLoaded('cashRegisterShift', fn () => (new CashRegisterShiftResource($this->cashRegisterShift))->resolve()),
            'items' => $this->relationLoaded('items')
                ? SalesOrderItemResource::collection($this->items)->resolve()
                : [],
            'payments' => $this->relationLoaded('payments')
                ? SalesOrderPaymentResource::collection($this->payments)->resolve()
                : [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
