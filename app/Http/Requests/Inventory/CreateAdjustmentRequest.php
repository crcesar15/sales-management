<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::STOCK_ADJUST->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'expiry_date' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $variantId = $this->input('product_variant_id');
                    if ($variantId) {
                        $variant = ProductVariant::find($variantId);
                        if ($variant instanceof ProductVariant && $variant->has_expiration && ! $value) {
                            $fail('The expiry date is required for this product variant.');
                        }
                    }
                },
            ],
            'batch_identifier' => ['nullable', 'string', 'max:255'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', Rule::enum(AdjustmentReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
