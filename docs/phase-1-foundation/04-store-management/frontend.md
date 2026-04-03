# Store Management — Frontend

## Pages

```
resources/js/Pages/Stores/
├── Index.vue           // Paginated store list with search and status filter
├── Show.vue            // Store detail: info + assigned users management
├── Create/
│   └── Index.vue       // Create store form with logo upload
└── Edit/
    └── Index.vue       // Edit store form with current logo preview
```

## Shared Components

```
resources/js/Components/Stores/
├── StoreStatusBadge.vue      // Badge for active/inactive
├── StoreLogo.vue             // Logo display with fallback placeholder
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
| `Avatar` | Store logo thumbnail |
| `Button` | Create, edit, view actions |

### Filtering
Use `router.visit(route('stores.index'), { data: filters, preserveState: true, replace: true })` with debounced watch on filter values.

---

## Create / Edit Form

### Form Handling
Use **VeeValidate + Yup** (not Inertia's `useForm`) with `toTypedSchema()` for type-safe validation.

Submit via `router.post()` / `router.put()` with `onSuccess`/`onError` callbacks. On error, show toast and call `setErrors()`.

For file uploads with `PUT`, use `router.post()` with `_method: 'PUT'` (Laravel method spoofing for multipart).

### PrimeVue Components
| Component | Usage |
|---|---|
| `InputText` | Name, code, address fields |
| `Dropdown` | Status selection (active / inactive) |
| `FileUpload` | Logo upload with preview |
| `Image` | Current logo preview |
| `Button` | Submit with loading state |
| `FloatLabel` | Label wrapper for clean layout |
| `InlineMessage` | Show field errors beneath each input |

### Logo Upload
- Show current logo preview in Edit form
- "Remove Logo" button calls `DELETE /stores/{id}/logo`
- Validate file type and size on frontend before upload (max 2MB)

---

## Show Page (`Show.vue`)

### Structure
- Store info summary card
- Assigned users DataTable with name, email, role, actions
- User assignment form: dropdown pickers for user and role
- ConfirmDialog before removing a user

### User Assignment
- Pass `availableUsers` as an Inertia prop (no separate API call needed)
- Use `preserveScroll: true` on user removal

### PrimeVue Components (Show page)
| Component | Usage |
|---|---|
| `Card` | Store info summary card |
| `DataTable` | Assigned users list |
| `Column` | User name, email, role, actions |
| `Dropdown` | User picker and role picker for assignment form |
| `Button` | Assign user, remove user |
| `ConfirmDialog` | Confirm before removing a user from store |
| `Tag` | Role badge |

---

## Ziggy Routes
```js
route('stores.index')                             // GET /stores
route('stores.create')                            // GET /stores/create
route('stores.store')                             // POST /stores
route('stores.show', { store: 1 })                // GET /stores/1
route('stores.edit', { store: 1 })                // GET /stores/1/edit
route('stores.update', { store: 1 })              // PUT /stores/1
route('stores.status', { store: 1 })              // PATCH /stores/1/status
route('stores.logo.remove', { store: 1 })         // DELETE /stores/1/logo
route('stores.users.assign', { store: 1 })        // POST /stores/1/users
route('stores.users.role', { store: 1, user: 2 }) // PATCH /stores/1/users/2
route('stores.users.remove', { store: 1, user: 2}) // DELETE /stores/1/users/2
```

---

## Good Practices
- Use `form.post()` with `_method: 'PUT'` for multipart updates
- Show current logo in Edit form with dedicated "Remove Logo" button
- Pass `availableUsers` as Inertia prop — no separate API calls
- Use `preserveScroll: true` on user removal
- Validate logo file size on frontend before upload
- Display field errors inline beneath each field
- Use `Tag` severity mapping: `active → success`, `inactive → danger`
