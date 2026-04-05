<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

use function assert;

final class SyncVariantImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PRODUCTS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['required', 'integer', 'exists:media,id'],
        ];
    }

    /**
     * Verify all media IDs belong to the product.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $product = $this->route('product');
            assert($product instanceof Product);
            $mediaIds = $this->input('media_ids', []);
            $productMediaIds = $product->getMedia('images')->pluck('id')->toArray();
            $invalid = array_diff($mediaIds, $productMediaIds);

            if (! empty($invalid)) {
                $validator->errors()->add('media_ids', 'All media must belong to this product.');
            }
        });
    }
}
