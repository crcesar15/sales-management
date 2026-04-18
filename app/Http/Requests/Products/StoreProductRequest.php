<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasVariants = $this->boolean('has_variants');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:350'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'measurement_unit_id' => ['nullable', 'exists:measurement_units,id'],
            'status' => ['required', 'in:active,inactive,archived'],
            'categories_ids' => ['nullable', 'array'],
            'categories_ids.*' => ['exists:categories,id'],
            'price' => [$hasVariants ? 'nullable' : 'required', 'numeric', 'min:0'],
            'stock' => [$hasVariants ? 'nullable' : 'required', 'integer', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'pending_media_ids' => ['nullable', 'array'],
            'pending_media_ids.*' => ['exists:pending_media_uploads,id,user_id,' . $this->user()?->id],
            'has_variants' => ['sometimes', 'boolean'],
            'options' => ['required_if:has_variants,true', 'array', 'min:1'],
            'options.*.name' => ['required', 'string', 'max:150'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*' => ['string', 'max:50'],
            'variants' => ['required_if:has_variants,true', 'array', 'min:1'],
            'variants.*.option_values' => ['required', 'array', 'min:1'],
            'variants.*.option_values.*' => ['required', 'string', 'max:50'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.pending_media_ids' => ['nullable', 'array'],
            'variants.*.pending_media_ids.*' => ['integer', 'exists:pending_media_uploads,id,user_id,' . $this->user()?->id],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('has_variants')) {
                return;
            }

            /** @var array<int, array{name: string, values: array<int, string>}> $optionsRaw */
            $optionsRaw = $this->input('options', []);
            /** @var array<int, array{option_values: array<string, string>}> $variantsRaw */
            $variantsRaw = $this->input('variants', []);

            $optionNames = collect($optionsRaw)->pluck('name')->sort()->values()->toArray();
            $optionValuesLookup = collect($optionsRaw)->mapWithKeys(fn (array $o): array => [$o['name'] => $o['values']]);

            $seenCombinations = [];

            foreach ($variantsRaw as $index => $variant) {
                $ov = $variant['option_values'];
                $ovKeys = collect(array_keys($ov))->sort()->values()->toArray();

                if ($ovKeys !== $optionNames) {
                    $validator->errors()->add(
                        "variants.{$index}.option_values",
                        __('Option values must match the defined options.'),
                    );

                    continue;
                }

                foreach ($ov as $optionName => $value) {
                    $validValues = $optionValuesLookup[$optionName] ?? [];
                    if (! in_array($value, $validValues, true)) {
                        $validator->errors()->add(
                            "variants.{$index}.option_values.{$optionName}",
                            __(':value is not a valid value for option :option.', ['value' => $value, 'option' => $optionName]),
                        );
                    }
                }

                $comboKey = collect($ov)->sortKeys()->toJson();
                if (isset($seenCombinations[$comboKey])) {
                    $validator->errors()->add(
                        "variants.{$index}.option_values",
                        __('Duplicate variant combination detected.'),
                    );
                }
                $seenCombinations[$comboKey] = true;
            }
        });
    }
}
