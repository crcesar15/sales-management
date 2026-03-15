# Task 01: Vendor Management — Frontend

## Pages (Inertia + Vue 3)

| File                                   | Route               | Description                        |
|----------------------------------------|---------------------|------------------------------------|
| `Pages/Vendors/Index.vue`              | `/vendors`          | List with search, filter, paginate |
| `Pages/Vendors/Create.vue`             | `/vendors/create`   | Create form                        |
| `Pages/Vendors/Edit.vue`               | `/vendors/{id}/edit`| Edit form                          |

## Components

| File                                          | Purpose                                      |
|-----------------------------------------------|----------------------------------------------|
| `Components/Vendors/VendorForm.vue`           | Shared create/edit form                      |
| `Components/Vendors/AdditionalContactsEditor.vue` | Dynamic array editor for contacts        |
| `Components/Vendors/VendorStatusBadge.vue`    | Color-coded status badge                     |

## PrimeVue Components Used

| Component       | Usage                                     |
|-----------------|-------------------------------------------|
| `DataTable`     | Vendor list with sorting                  |
| `Column`        | Table columns                             |
| `InputText`     | Name, email, phone, address fields        |
| `Textarea`      | Details field                             |
| `Select`        | Status dropdown                           |
| `Button`        | Actions (edit, delete, create)            |
| `ConfirmDialog` | Delete confirmation                       |
| `Tag`           | Status badge in table                     |
| `IconField`     | Search input with icon                    |

## Key Patterns

**Search + filter with Inertia:**
```js
// Debounced search triggers Inertia.get with query params
router.get('/vendors', { search, status }, { preserveState: true, replace: true })
```

**AdditionalContactsEditor** — renders a dynamic list of contact rows; each row has name, phone, email, role inputs; `v-model` bound to `additional_contacts` array.

**Delete guard feedback:**
- Send DELETE request; if 409 returned, show a `Toast` error: "Vendor cannot be deleted — existing purchase orders or catalog entries found."

## Pinia Store
- No dedicated store needed; use Inertia shared props for vendor data
- Use `useForm` from `@inertiajs/vue3` for form state and error handling

## Notes
- Status filter uses a `Select` with options: `All`, `Active`, `Inactive`, `Archived`
- Archived vendors should appear visually muted in the list
- Pagination via Inertia's built-in paginator component
