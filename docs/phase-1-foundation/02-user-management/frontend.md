# User Management — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Users/
├── Index.vue       // Paginated user list with search/filters
├── Create.vue      // Create user form
└── Edit.vue        // Edit user form
```

### Shared Components
```
resources/js/Components/Users/
├── UserStatusBadge.vue    // Colored badge for active/inactive/archived
└── UserStoreAssignment.vue // Manage store assignments inline
```

---

## Index Page (`Index.vue`)

### PrimeVue Components
| Component | Usage |
|---|---|
| `DataTable` | Paginated user list |
| `Column` | Name, email, status, stores, actions |
| `InputText` | Search box |
| `Dropdown` | Status filter |
| `MultiSelect` | Store filter |
| `Tag` | Status badge |
| `Button` | Create, edit, delete actions |
| `ConfirmDialog` | Confirm before deleting |

### Inertia Link for Navigation
```vue
<Link :href="route('users.create')">
  <Button label="New User" icon="pi pi-plus" />
</Link>
```

### Filters with Inertia
Use `router.get()` to apply filters without losing state:
```js
const filters = reactive({ search: '', status: '', store_id: '' })

watch(filters, () => {
  router.get(route('users.index'), filters, {
    preserveState: true,
    replace: true,
  })
})
```

---

## Create / Edit Form

### Inertia Form
```vue
<script setup>
const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  username: '',
  phone: '',
  password: '',
  password_confirmation: '',
  status: 'active',
  role: '',
  store_ids: [],
})

const submit = () => {
  form.post(route('users.store'))
}
</script>
```

### PrimeVue Components
| Component | Usage |
|---|---|
| `InputText` | All text fields |
| `Password` | Password + confirmation |
| `Dropdown` | Status, role selection |
| `MultiSelect` | Store assignment |
| `Calendar` | Date of birth |
| `Button` | Submit with loading state |
| `FloatLabel` | Label wrapping for clean form layout |

---

## Pinia Store
No dedicated Pinia store needed. Use Inertia shared props for the authenticated user and per-page props for user lists.

For global state (e.g., available roles/stores for dropdowns), pass as Inertia page props from the controller.

---

## Good Practices
- Pass `roles` and `stores` as Inertia props (not fetched via separate API calls)
- Use `preserveState: true` on filter changes to avoid scroll reset
- Show `form.errors` inline beneath each field
- Disable the delete button for the currently authenticated user
- Use `Tag` component with severity mapping: `active → success`, `inactive → warning`, `archived → danger`
