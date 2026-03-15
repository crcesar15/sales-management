# Frontend — Customer Management

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `Customers/Index` | `resources/js/Pages/Customers/Index.vue` | Searchable paginated list |
| `Customers/Create` | `resources/js/Pages/Customers/Create.vue` | Create form |
| `Customers/Edit` | `resources/js/Pages/Customers/Edit.vue` | Edit form |

## Components to Create
| Component | Purpose |
|---|---|
| `Customers/CustomerForm.vue` | Shared form fields for Create/Edit |
| `Customers/CustomerSearchDropdown.vue` | Async typeahead for POS use |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Customer list table |
| `InputText` | Search bar, form fields |
| `Button` | Actions (save, delete, cancel) |
| `AutoComplete` | POS customer typeahead search |
| `ConfirmDialog` | Delete confirmation |
| `Toast` | Success / error feedback |
| `Tag` | Display customer has/no tax ID |

## Key Patterns

**Debounced Search (List)**
```js
const search = ref(router.page.props.filters.search ?? '')
watchDebounced(search, (val) => router.get('/customers', { search: val }, { preserveState: true }), { debounce: 300 })
```

**POS Customer Autocomplete**
```vue
<AutoComplete v-model="selectedCustomer" :suggestions="results"
  @complete="searchCustomers" optionLabel="display_name" placeholder="Walk-in / Search customer..." />
```
- Calls `/customers/search?q=` via `axios.get`
- On clear → sets `customer_id` to `null` (walk-in)

**Delete with Confirmation**
```js
const deleteCustomer = (id) => {
  confirm.require({ message: 'Delete this customer?', accept: () => router.delete(`/customers/${id}`) })
}
```

## Form Fields Layout
- Row 1: `first_name` | `last_name`
- Row 2: `email` | `phone`
- Row 3: `tax_id` | `tax_id_name`

## Notes
- Inertia `useForm()` for all create/edit mutations
- Display validation errors inline under each field via `form.errors.field`
- List page uses Inertia `router.get` for search/pagination (no separate API call)
