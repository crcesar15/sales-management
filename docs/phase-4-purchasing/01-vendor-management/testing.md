# Task 01: Vendor Management — Testing

## Test File Locations

| File                                                   | Type    |
|--------------------------------------------------------|---------|
| `tests/Feature/Vendors/VendorCrudTest.php`             | Feature |
| `tests/Feature/Vendors/VendorDeletionGuardTest.php`    | Feature |
| `tests/Unit/Models/VendorTest.php`                     | Unit    |

## Test Cases

### VendorCrudTest
- **List**: authenticated admin can retrieve paginated vendor list
- **Search**: search by `fullname` returns matching vendors; search by `email` returns matching vendors
- **Filter by status**: only vendors with matching status are returned
- **Create**: valid payload creates vendor; `additional_contacts` JSON is persisted correctly
- **Create validation**: missing `fullname` returns 422; duplicate `email` returns 422
- **Update**: valid update payload persists changes
- **Update email uniqueness**: can update vendor keeping same email (ignore self); cannot use another vendor's email

### VendorDeletionGuardTest
- **Delete success**: vendor with no POs and no catalog entries is deleted (204)
- **Delete blocked by PO**: vendor with a purchase order returns 409 with error message
- **Delete blocked by catalog**: vendor with a catalog entry returns 409 with error message
- **Permission gate**: user without `vendors.manage` gets 403 on all endpoints

### VendorTest (Unit)
- `additional_contacts` attribute is cast to array
- `meta` attribute is cast to array
- `purchaseOrders` relationship returns correct related models
- `catalogEntries` relationship returns correct related models

## Coverage Goals
- [ ] All CRUD endpoints covered with happy-path and validation tests
- [ ] Deletion guard tested for both blocking scenarios
- [ ] Permission middleware tested (403 for unauthorized)
- [ ] JSON casting tested at model level
- [ ] `additional_contacts` array shape validated in request test

## Notes
- Use Pest 3 with `RefreshDatabase` trait
- Use factories for `Vendor`, `PurchaseOrder`, `Catalog` models
- Seed `vendors.manage` permission in test setup via `Permission::create(['name' => 'vendors.manage'])`
