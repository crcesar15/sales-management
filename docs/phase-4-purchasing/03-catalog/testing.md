# Task 03: Catalog — Testing

## Test File Locations

| File                                                | Type    |
|-----------------------------------------------------|---------|
| `tests/Feature/Catalog/CatalogIndexTest.php`       | Feature |
| `tests/Feature/Catalog/CatalogFilterTest.php`       | Feature |
| `tests/Feature/Api/CatalogApiTest.php`              | Feature |

## Test Cases

### CatalogIndexTest
- **List loads**: authenticated admin can view the product-centric catalog page
- **Groups by variant**: catalog entries are returned with product variant relationships loaded
- **Empty catalog**: page loads correctly when no catalog entries exist
- **Permission gate**: user without `catalog.view` gets 403

### CatalogFilterTest
- **Filter by status**: `active` filter returns only active entries; `all` returns everything
- **Filter by vendor**: `vendor_id` filter returns only entries for that vendor
- **Search by product name**: text filter matches product name via variant relationship
- **Combined filters**: status + vendor + search filters work together
- **Pagination**: results paginate correctly with query string preserved

### CatalogApiTest
- **API list**: `GET /api/v1/catalog` returns paginated catalog entries with relationships
- **API variant vendors**: `GET /api/v1/catalog/variants/{id}/vendors` returns entries for a specific variant sorted by price
- **API variant not found**: returns 404 for non-existent variant ID
- **API permission gate**: user without `catalog.view` gets 403
- **API inactive filter**: `?status=inactive` returns only inactive entries

## Coverage Goals
- [ ] Product-centric listing returns entries grouped by variant (via eager-loaded relationships)
- [ ] Active/inactive/all filtering works correctly
- [ ] Vendor filtering works correctly
- [ ] Text search filters by product name
- [ ] API endpoints return correct data structure
- [ ] Permission gates block unauthorized access

## Notes
- Use Pest 3 with `RefreshDatabase`
- Create catalog entries via `Catalog::factory()` with required vendor and variant relationships
- Test with multiple vendors supplying the same variant (to verify grouping/comparison)
- Test with vendors supplying different purchase units for the same variant (unique constraint allows this)