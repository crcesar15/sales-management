# Task 02: Vendor Catalog — Testing

## Test File Locations

| File                                                       | Type    |
|------------------------------------------------------------|---------|
| `tests/Feature/Vendors/VendorCatalogCrudTest.php`          | Feature |
| `tests/Feature/Vendors/VendorCatalogConstraintsTest.php`   | Feature |
| `tests/Unit/Models/CatalogTest.php`                        | Unit    |

## Test Cases

### VendorCatalogCrudTest
- **List**: authenticated admin can list catalog entries for a vendor
- **Create**: valid payload creates entry with all new columns (`purchase_unit_id`, `conversion_factor`, `minimum_order_quantity`, `lead_time_days`)
- **Create defaults**: `conversion_factor` defaults to 1 when not provided
- **Update**: can update price, status, and purchasing columns
- **Toggle status**: changing status from `active` to `inactive` persists correctly
- **Delete**: catalog entry is deleted when no POs reference it

### VendorCatalogConstraintsTest
- **Unique constraint**: creating a duplicate vendor+variant combination returns 422
- **Update ignores self**: updating an entry does not trigger unique violation for same vendor+variant
- **conversion_factor validation**: value < 1 returns 422
- **Active filter**: PO builder endpoint only returns `active` entries
- **Permission gate**: user without `vendors.manage` gets 403

### CatalogTest (Unit)
- `scopeActive` only returns entries with `status = active`
- `purchaseUnit` relationship resolves to `MeasurementUnit`
- `vendor` and `productVariant` relationships resolve correctly

## Coverage Goals
- [ ] All CRUD operations covered
- [ ] Unique constraint (DB + application) tested
- [ ] `conversion_factor` minimum validation tested
- [ ] Active scope tested (used by PO creation)
- [ ] New migration columns are correctly stored and retrieved

## Notes
- Use Pest 3 with `RefreshDatabase`
- Factory should provide defaults for new columns; use `->nullable()` for optional fields
- Test the migration itself by checking column existence with `Schema::hasColumn('catalog', 'purchase_unit_id')`
