# Testing — Customer Management

## Test File Locations
```
tests/Feature/CustomerManagementTest.php
tests/Unit/Models/CustomerTest.php
```

## Feature Test Cases

### Listing
- Authenticated user with `customers.manage` can access customer list
- List is paginated (20 per page)
- Search by first name, last name, email, phone returns matching results
- Unauthenticated user is redirected

### Create
- Valid payload creates a customer and redirects
- Duplicate email returns validation error
- All nullable fields can be omitted without error
- User without `customers.manage` gets 403

### Update
- Valid payload updates customer fields
- Email unique rule ignores the customer being updated
- User without permission gets 403

### Delete
- Customer with no sales orders is deleted successfully
- Customer with associated sales orders returns 422 with error message
- User without permission gets 403

### POS Search (`/customers/search`)
- Returns max 10 results
- Matches on partial first name, last name, email, phone
- User with `sales.create` permission can access
- User without `sales.create` gets 403
- Returns `display_name`, `email`, `phone`, `id` fields

## Unit Test Cases (`CustomerTest.php`)
- `displayName` accessor returns `first last` when both present
- `displayName` falls back to email when name is empty
- `displayName` falls back to phone when name and email are empty
- `salesOrders()` relationship returns correct instances

## Coverage Goals
- 100% of controller actions covered by feature tests
- Deletion guard logic covered
- POS search endpoint covered with permission variants
