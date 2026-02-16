<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $reception_order_id
 * @property string|null $expiry_date
 * @property int $initial_quantity
 * @property int $remaining_quantity
 * @property int $missing_quantity
 * @property int $sold_quantity
 * @property int $transferred_quantity
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\BatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereInitialQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereMissingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereReceptionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereRemainingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereSoldQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereTransferredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereUpdatedAt($value)
 */
	final class Batch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\BrandFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereUpdatedAt($value)
 */
	final class Brand extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $vendor_id
 * @property int $product_variant_id
 * @property float $price
 * @property string|null $payment_terms
 * @property string|null $details
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\CatalogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereVendorId($value)
 */
	final class Catalog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 */
	final class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $tax_id
 * @property string|null $tax_id_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\CustomerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxIdName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 */
	final class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\MeasurementUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereUpdatedAt($value)
 */
	final class MeasurementUnit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $upload_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingMedia whereUploadToken($value)
 */
	final class PendingMedia extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $brand_id
 * @property int|null $measurement_unit_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Brand|null $brand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read \App\Models\MeasurementUnit|null $measurementUnit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductOption> $options
 * @property-read int|null $options_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMeasurementUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 */
	final class Product extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductOptionValue> $values
 * @property-read int|null $values_count
 * @method static \Database\Factories\ProductOptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereUpdatedAt($value)
 */
	final class ProductOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_option_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductOption $option
 * @method static \Database\Factories\ProductOptionValueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereProductOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereValue($value)
 */
	final class ProductOptionValue extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string|null $identifier
 * @property numeric $price
 * @property int $stock
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read mixed $name
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductOptionValue> $values
 * @property-read int|null $values_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vendor> $vendors
 * @property-read int|null $vendors_count
 * @method static \Database\Factories\ProductVariantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereUpdatedAt($value)
 */
	final class ProductVariant extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $product_option_value_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereProductOptionValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereProductVariantId($value)
 */
	final class ProductVariantOptionValue extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $vendor_id
 * @property string $status
 * @property string|null $order_date
 * @property string|null $expected_arrival_date
 * @property float|null $sub_total
 * @property float|null $discount
 * @property float|null $total
 * @property string|null $notes
 * @property string|null $proof_of_payment_type
 * @property string|null $proof_of_payment_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Vendor $vendor
 * @method static \Database\Factories\PurchaseOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereExpectedArrivalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereProofOfPaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereProofOfPaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereVendorId($value)
 */
	final class PurchaseOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $user_id
 * @property int $vendor_id
 * @property string|null $reception_date
 * @property string|null $notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ReceptionOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereReceptionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereVendorId($value)
 */
	final class ReceptionOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property string $status
 * @property string $payment_method
 * @property string|null $notes
 * @property float $sub_total
 * @property float $discount
 * @property float $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\SalesOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUserId($value)
 */
	final class SalesOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $name
 * @property string $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 */
	final class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\StoreFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereUpdatedAt($value)
 */
	final class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $username
 * @property string|null $phone
 * @property string $status
 * @property string|null $date_of_birth
 * @property string|null $additional_properties
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAdditionalProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	final class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $fullname
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $details
 * @property string $status
 * @property array<array-key, mixed>|null $additional_contacts
 * @property string|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @method static \Database\Factories\VendorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereAdditionalContacts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereFullname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereUpdatedAt($value)
 */
	final class Vendor extends \Eloquent {}
}

