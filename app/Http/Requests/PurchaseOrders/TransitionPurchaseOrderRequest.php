<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders;

use App\Enums\PermissionsEnum;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class TransitionPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_APPROVE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,sent,received'],
            'completion_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->string('status')->toString() !== 'received') {
                return;
            }

            /** @var PurchaseOrder|null $purchaseOrder */
            $purchaseOrder = $this->route('purchaseOrder');

            if ($purchaseOrder === null) {
                return;
            }

            $purchaseOrder->loadMissing('lineItems.receptionOrderItems.receptionOrder');

            if (! $purchaseOrder->is_fully_received && empty($this->input('completion_notes'))) {
                $validator->errors()->add(
                    'completion_notes',
                    'A reason is required when marking a purchase order as received before all items have been fully received.',
                );
            }
        });
    }
}
