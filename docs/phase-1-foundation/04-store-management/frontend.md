# Store Management — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Stores/
├── Index.vue    // Paginated store list with search and status filter
├── Create.vue   // Create store form with logo upload
├── Edit.vue     // Edit store form with current logo preview
└── Show.vue     // Store detail: info + assigned users management
```

### Shared Components
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

### Filters with Inertia
```vue
<script setup lang="ts">
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  stores: { data: Store[]; meta: PaginationMeta }
  filters: { search?: string; status?: string }
}>()

const filters = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
})

watch(filters, () => {
  router.get(route('stores.index'), filters, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })
</script>
```

---

## Create / Edit Form

### Inertia Form Setup
```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps<{
  store?: Store // undefined for Create, populated for Edit
}>()

const form = useForm({
  name:    props.store?.name    ?? '',
  code:    props.store?.code    ?? '',
  address: props.store?.address ?? '',
  status:  props.store?.status  ?? 'active',
  logo:    null as File | null,
})

const logoPreview = ref<string | null>(props.store?.logo_url ?? null)

function onLogoChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null
  form.logo = file
  if (file) {
    logoPreview.value = URL.createObjectURL(file)
  }
}

function submit() {
  if (props.store) {
    form.post(route('stores.update', props.store.id), {
      _method: 'PUT', // method spoofing for multipart
    })
  } else {
    form.post(route('stores.store'))
  }
}
</script>
```

> **Note:** For file uploads with `PUT`, use `form.post()` with `_method: 'PUT'` (Laravel method spoofing), since multipart forms cannot use PUT natively.

### PrimeVue Components
| Component | Usage |
|---|---|
| `InputText` | Name, code, address fields |
| `Dropdown` | Status selection (active / inactive) |
| `FileUpload` | Logo upload with preview |
| `Image` | Current logo preview |
| `Button` | Submit with loading state (`:loading="form.processing"`) |
| `FloatLabel` | Label wrapper for clean layout |
| `InlineMessage` | Show `form.errors.field` beneath each input |

### Logo Upload with Preview
```vue
<template>
  <div class="flex flex-col gap-2">
    <label>Store Logo</label>

    <!-- Current logo preview -->
    <div v-if="logoPreview" class="flex items-center gap-4">
      <Image :src="logoPreview" width="80" height="80" imageClass="rounded-lg object-cover" />
      <Button
        label="Remove"
        severity="danger"
        size="small"
        text
        @click="removeLogo"
      />
    </div>

    <input
      type="file"
      accept="image/jpeg,image/png,image/webp"
      @change="onLogoChange"
    />
    <small class="text-red-500" v-if="form.errors.logo">{{ form.errors.logo }}</small>
  </div>
</template>
```

---

## Show Page (`Show.vue`)

### Structure
```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps<{
  store: StoreDetail // includes .users
}>()

const assignForm = useForm({
  user_id: null as number | null,
  role_id: null as number | null,
})

function assignUser() {
  assignForm.post(route('stores.users.assign', props.store.id), {
    onSuccess: () => assignForm.reset(),
  })
}

function removeUser(userId: number) {
  router.delete(route('stores.users.remove', [props.store.id, userId]), {
    preserveScroll: true,
  })
}
</script>
```

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

## Pinia Store
No dedicated Pinia store needed. All store data is passed as Inertia page props. For the user assignment dropdown, pass available users from the controller as a prop.

```php
// In StoreController::show()
'availableUsers' => User::whereDoesntHave('stores', fn ($q) => $q->where('store_id', $store->id))
    ->select('id', 'first_name', 'last_name', 'email')
    ->get()
    ->map(fn ($u) => ['id' => $u->id, 'label' => $u->full_name]),
```

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
- Use `form.post()` with `_method: 'PUT'` for multipart updates — standard `form.put()` does not support file uploads
- Show the current logo in the Edit form and allow removal with a dedicated "Remove Logo" button (calls `DELETE /stores/{id}/logo`)
- Pass `availableUsers` as an Inertia prop for the assignment dropdown — do not make a separate API call
- Use `preserveScroll: true` on user removal so the page does not jump to the top
- Validate logo file size on the frontend (before upload) to give immediate feedback
- Display `form.errors` inline beneath each field
- Use `Tag` severity mapping: `active → success`, `inactive → danger`
