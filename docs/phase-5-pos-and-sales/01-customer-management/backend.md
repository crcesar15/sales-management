# Backend — Customer Management

## Implementation Steps
1. Generate migration `create_customers_table` with all columns
2. Create `Customer` Eloquent model with `$fillable` and `salesOrders()` relationship
3. Create `CustomerController` (Index, Create, Store, Edit, Update, Destroy)
4. Create `StoreCustomerRequest` and `UpdateCustomerRequest` form requests
5. Add `customers/search` route + lightweight JSON response method
6. Register `customers.manage` and `sales.create` permissions via seeder
7. Protect all routes with `can:customers.manage` middleware (except search: `can:sales.create`)
8. Add deletion guard in `Destroy` action — return 422 if customer has sales orders

## Key Files to Create
```
app/Models/Customer.php
app/Http/Controllers/CustomerController.php
app/Http/Requests/StoreCustomerRequest.php
app/Http/Requests/UpdateCustomerRequest.php
database/migrations/xxxx_create_customers_table.php
```

## Key Patterns

**Deletion Guard**
```php
public function destroy(Customer $customer)
{
    abort_if($customer->salesOrders()->exists(), 422, 'Customer has associated sales orders.');
    $customer->delete();
    return back()->with('success', 'Customer deleted.');
}
```

**Search Query Scope**
```php
// Customer::scopeSearch($query, $term)
$query->where(fn($q) => $q
    ->where('first_name', 'like', "%{$term}%")
    ->orWhere('last_name', 'like', "%{$term}%")
    ->orWhere('email', 'like', "%{$term}%")
    ->orWhere('phone', 'like', "%{$term}%")
);
```

**Display Name Accessor**
```php
protected function displayName(): Attribute
{
    return Attribute::get(fn() =>
        trim("{$this->first_name} {$this->last_name}") ?: $this->email ?? $this->phone ?? 'Unknown'
    );
}
```

## Gotchas
- Nullable unique email: use `Rule::unique('customers','email')->ignore($customer)` in update request
- POS search must be fast — limit to 10 results, avoid `SELECT *`
- `first_name` and `last_name` are both nullable; never assume either exists
