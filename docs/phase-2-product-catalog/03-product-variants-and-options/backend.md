# Backend — Product Variants & Options

## Key Files to Create

```
app/
├── Http/Controllers/Products/
│   ├── ProductOptionController.php
│   ├── OptionValueController.php
│   └── ProductVariantController.php
├── Http/Requests/Products/
│   ├── StoreProductOptionRequest.php
│   ├── StoreOptionValueRequest.php
│   ├── GenerateVariantsRequest.php
│   ├── StoreVariantRequest.php
│   └── UpdateVariantRequest.php
├── Http/Resources/Products/
│   ├── ProductOptionResource.php
│   └── ProductVariantResource.php
├── Models/
│   ├── ProductOption.php
│   ├── ProductOptionValue.php
│   └── ProductVariant.php
└── Services/Products/
    └── ProductVariantService.php
```

## Implementation Steps

1. **Models** — define relationships; `ProductVariant` uses `LogsActivity`; no soft-deletes on variants (use `status = 'archived'` instead)
2. **`ProductVariantService`** — centralize all logic:
   - `generateVariants(Product $product, array $optionsData): array`
   - `storeManual(Product $product, array $data): ProductVariant`
   - `update(ProductVariant $variant, array $data): ProductVariant`
   - `destroy(ProductVariant $variant): void`
   - `isDuplicateCombination(Product $product, array $optionValueIds, ?int $excludeVariantId): bool`
3. **Cartesian product helper** — compute all combinations; wrap in a `DB::transaction()` when creating variants
4. **Duplicate check** — before creating any variant, verify no existing variant on the same product has the same set of option value IDs
5. **Controllers** — load product via route model binding; call service; return `back()` or `Inertia::render()` redirect

## Key Patterns

**Generating all combinations (in service):**
```php
private function cartesian(array $sets): array {
    return array_reduce($sets, fn($c, $s) =>
        array_merge(...array_map(fn($combo) => array_map(fn($v) => array_merge($combo, [$v]), $s), $c)),
    [[]]);
}

public function generateVariants(Product $product, array $optionsData): array {
    if ($product->variants()->exists()) {
        throw new HttpResponseException(response()->json([
            'message' => 'Cannot auto-generate: this product already has variants.'
        ], 422));
    }
    // 1. Create ProductOption + ProductOptionValue records
    // 2. Build value-ID arrays per option
    // 3. Cartesian product → array of [valueId, valueId, ...]
    // 4. DB::transaction: foreach combination → create variant → sync pivot
}
```

**Duplicate combination check:**
```php
public function isDuplicateCombination(Product $product, array $valueIds, ?int $excludeId = null): bool {
    return $product->variants()
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->whereHas('optionValues', fn($q) => $q->whereIn('id', $valueIds), '=', count($valueIds))
        ->whereDoesntHave('optionValues', fn($q) => $q->whereNotIn('id', $valueIds))
        ->exists();
}
```

**Pivot sync (no timestamps):**
```php
$variant->optionValues()->sync($optionValueIds);
```

**Option value deletion guard:**
```php
if ($value->variants()->exists()) {
    throw new HttpResponseException(response()->json([
        'message' => 'Cannot delete option value: it is assigned to one or more variants.'
    ], 422));
}
```

## Validation Rules
```php
// GenerateVariantsRequest
'options'              => 'required|array|min:1',
'options.*.name'       => 'required|string|max:100',
'options.*.values'     => 'required|array|min:1',
'options.*.values.*'   => 'required|string|max:100',

// StoreVariantRequest
'identifier'         => 'nullable|string|max:100|unique:product_variants,identifier',
'price'              => 'required|numeric|min:0',
'stock'              => 'required|integer|min:0',
'status'             => 'required|in:active,inactive,archived',
'option_value_ids'   => 'required|array',
'option_value_ids.*' => 'exists:product_option_values,id',
```

## Gotchas
- `identifier` uniqueness: `unique:product_variants,identifier` ignores NULL — multiple null identifiers are valid
- Wrap variant batch creation in `DB::transaction()` — partial failures leave orphaned options otherwise
- Use `withoutTimestamps()` on the pivot relationship to avoid timestamp column errors
- Stock is **read-only** from this module — only inventory adjustments (Phase 3) should write it
