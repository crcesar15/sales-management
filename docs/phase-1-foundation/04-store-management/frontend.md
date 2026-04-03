# Store Management — Frontend

## Pages

```
resources/js/Pages/Stores/
├── Index.vue           // Paginated store list with search and status filter
├── Show.vue            // Store detail: info + assigned users management
├── Create/
│   └── Index.vue       // Create store form
└── Edit/
    └── Index.vue       // Edit store form
```

## Shared Components

```
resources/js/Components/Stores/
├── StoreStatusBadge.vue      // Badge for active/inactive
└── StoreUserAssignment.vue   // User assignment panel used in Show.vue
```

---

## Index Page (`Index.vue`)

### PrimeVue Components
| Component | Usage |
|---|---|
| `DataTable` | Paginated store list |
| `Column` | Name, code, status, user count, actions |
| `InputText` | Search by name or code |
| `Dropdown` | Status filter (all / active / inactive) |
| `Tag` | Status badge |
| `Button` | Create, edit, view, delete, restore actions |

### Filtering
Use `router.visit(route('stores.index'), { data: filters, preserveState: true, replace: true })` with debounced watch on filter values.

---

## Create / Edit Form

### Form Handling
Use **VeeValidate + Yup** (not Inertia's `useForm`) with `toTypedSchema()` for type-safe validation.

Submit via `router.post()` / `router.put()` with `onSuccess`/`onError` callbacks. On error, show toast and call `setErrors()`.

### Fields
| Field | Component | Validation |
|---|---|---|
| `name` | `InputText` | Required, max 100 |
| `code` | `InputText` | Required, max 20, unique |
| `address` | `InputText` | Nullable, max 255 |
| `city` | `InputText` | Nullable, max 100 |
| `state` | `InputText` | Nullable, max 100 |
| `zip_code` | `InputText` | Nullable, max 20 |
| `phone` | `InputText` | Nullable, max 30 |
| `email` | `InputText` | Nullable, valid email, max 150 |
| `status` | `Dropdown` | Required, active/inactive |

### PrimeVue Components
| Component | Usage |
|---|---|
| `InputText` | Name, code, address, city, state, zip_code, phone, email fields |
| `Dropdown` | Status selection (active / inactive) |
| `Button` | Submit with loading state |
| `FloatLabel` | Label wrapper for clean layout |
| `InlineMessage` | Show field errors beneath each input |

---

## Show Page (`Show.vue`)

### Structure
- Store info summary card (name, code, full address, phone, email, status)
- Assigned users DataTable with name, email, actions
- User assignment form: dropdown picker for user
- ConfirmDialog before removing a user
- Delete button (soft delete) with confirmation
- Restore button (visible only for soft-deleted stores)

### User Assignment
- Pass `availableUsers` as an Inertia prop (no separate API call needed)
- Use `preserveScroll: true` on user removal

### PrimeVue Components (Show page)
| Component | Usage |
|---|---|
| `Card` | Store info summary card |
| `DataTable` | Assigned users list |
| `Column` | User name, email, actions |
| `Dropdown` | User picker for assignment form |
| `Button` | Assign user, remove user, delete store, restore store |
| `ConfirmDialog` | Confirm before removing a user or deleting a store |
| `Tag` | Status badge |

---

## Ziggy Routes
```js
route('stores.index')                             // GET /stores
route('stores.create')                            // GET /stores/create
route('stores.store')                             // POST /stores
route('stores.show', { store: 1 })                // GET /stores/1
route('stores.edit', { store: 1 })                // GET /stores/1/edit
route('stores.update', { store: 1 })              // PUT /stores/1
route('stores.destroy', { store: 1 })             // DELETE /stores/1
route('stores.restore', { store: 1 })             // PATCH /stores/1/restore
route('stores.status', { store: 1 })              // PATCH /stores/1/status
route('stores.users.assign', { store: 1 })        // POST /stores/1/users
route('stores.users.remove', { store: 1, user: 2}) // DELETE /stores/1/users/2
```

---

## Good Practices
- Pass `availableUsers` as Inertia prop — no separate API calls
- Use `preserveScroll: true` on user removal
- Display field errors inline beneath each field
- Use `Tag` severity mapping: `active → success`, `inactive → danger`
- Group address fields (address, city, state, zip_code) together in the form layout
- Show delete confirmation dialog before soft-deleting a store
- Show restore button only when store is soft-deleted
