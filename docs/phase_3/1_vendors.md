# Task: Vendor Management

## Goal
Manage suppliers/vendors details.

## Technical Implementation

### 1. Database Schema
- [ ] **vendors**:
    -   `id`, `name`, `email` (nullable), `phone`, `address`, `tax_number`, `contact_person`.
    -   `created_at`, `updated_at`, `deleted_at` (SoftDeletes).

### 2. Backend Logic
- [ ] **Models**: `Vendor` (SoftDeletes, Auditable).
- [ ] **Controller**: `VendorController` (Resource).
- [ ] **Validation**: Name is required. Email must be valid format if present.

### 3. Frontend Implementation
- [ ] **Pages**: `Vendors/Index.vue`, `Create.vue`, `Edit.vue`.
- [ ] **Components**: Standard CRUD forms.

### 4. Verification
-   Create Vendor "Acme Corp".
-   Edit details.
-   Delete and verify soft delete.
