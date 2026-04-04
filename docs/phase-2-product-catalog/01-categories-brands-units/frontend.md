# Frontend — Categories, Brands & Measurement Units

## Current State

All three pages exist with full CRUD via API composables. They need refactoring to use Inertia props and `router` instead of API client calls.



| File | Status |
|------|--------|
| `Pages/Categories/Index.vue` | Exists — uses `useCategoryClient` API calls |
| `Pages/Categories/List/ItemEditor.vue` | Exists — Dialog component |
| `Pages/Brands/Index.vue` | Exists — use `useBrandClient` API calls |
| `Pages/Brands/List/ItemEditor.vue` | Exists — Dialog component |
| `Pages/MeasurementUnits/Index.vue` | Exists — use `useMeasurementUnitClient` API calls |
| `Pages/MeasurementUnits/List/ItemEditor.vue` | Exists — Dialog component |
| `Types/{category,brand,measurement-unit}-types.ts` | Exists |

## Refactoring Scope

- **List/fetch** → `router.visit()` with `preserveState: true` (server-side pagination/filtering via Inertia props)
  - **Create/update** → VeeValidate for Yup schema + Inertia `useForm` for submission
  - **Delete/restore** → Inertia `useForm({}).delete()` / `.put()` with `useConfirm`
  - **Remove** API composable imports (`useCategoryClient`, `useBrandClient`, `useMeasurementUnitClient`)

## Page Structure (no changes needed)

```
resources/js/Pages/
├── Categories/
│   ├── Index.vue              # DataTable + Dialog trigger
│   └── List/ItemEditor.vue    # Create/Edit Dialog modal
├── Brands/
│   ├── Index.vue
│   └── List/ItemEditor.vue
└── MeasurementUnits/
    ├── Index.vue
    └── List/ItemEditor.vue
```

## Key Inertia Patterns

### Props — received from controller

```ts
const props = defineProps<{
  categories: { data: Category[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }
  filters: { filter: string | null; status: string; order_by: string; order_direction: string; per_page: number }
}>()
```

### Server-side pagination/sorting/filtering

Replace `fetchBrandsApi()` calls with `router.visit()`:

```ts
router.visit(route('brands'), {
  data: { filter: val, status: status.value, page: 1 },
  preserveState: true,
  replace: true,
})
```

DataTable events (`@page`, `@sort`) and filter watchers should call `router.visit()` instead of the API fetch function.

### Form validation — VeeValidate + Yup

Use VeeValidate with Yup schemas for `toTypedSchema`. Submit via Inertia `useForm`.

 Schema definition:

```ts
import { useForm } from '@inertiajs/vue3'
import { toTypedSchema } from '@vee-validate/yup'
import { object, string } from 'yup'

const categorySchema = toTypedSchema(object({ name: string().required().max(50) }))
const brandSchema = toTypedSchema(object({ name: string().required().max(50) }))
const measurementUnitSchema = toTypedSchema(object({
  name: string().required().max(100),
  abbreviation: string().required().max(10),
}))
```

### ItemEditor — VeeValidate + Inertia integration

```vue
<Dialog v-model:visible="showModal" :header="t('Brand')" modal @hide="form.resetForm()">
  <div class="flex flex-col">
    <label for="name">{{ t('Name') }}</label>
    <InputText id="name" v-bind="name" v-model="name"
 :class="{ 'p-invalid': !!errors.name }" />
    <small v-if="errors.name" class="text-red-400 dark:text-red-300">{{ errors.name }}</small>
  </div>
  <!-- MeasurementUnits: second field -->
  <div v-if="isMeasurementUnit" class="flex flex-col mt-2">
    <label for="abbreviation">{{ t('Abbreviation') }}</label>
    <InputText id="abbreviation" v-bind="abbreviation" v-model="abbreviation" :class="{ 'p-invalid': !!errors.abbreviation }" />
    <small v-if="errors.abbreviation" class="text-red-400 dark:text-red-300">{{ errors.abbreviation }}</small>
  </div>
  <template #footer>
    <Button severity="secondary" :label="t('Cancel')" @click="showModal = false" />
    <Button :label="t('Save')" @click="onSubmit" />
  </template>
</Dialog>
```

### Submit handler — Inertia `useForm` in parent

```ts
const saveCategory = (data: { name: string }) => {
  if (selectedCategory.value) {
    useForm(data).put(route('categories.update', selectedCategory.value.id), {
      onSuccess: () => { showModal.value = false; toast.add(...) }
    })
  } else {
    useForm(data).post(route('categories.store'), {
      onSuccess: () => { showModal.value = false; toast.add(...) }
    })
  }
}
```

### Server-side validation errors → `setErrors()`

### Delete/restore with useConfirm

```ts
confirm.require({
  message: t("Are you sure you want to delete this brand?"),
  accept: () => {
    router.delete(route('brands.destroy', id), {
      onSuccess: () => toast.add({ severity: 'success', summary: t('Success'), detail: t('Brand deleted successfully') }),
    })
  },
})
```

## PrimeVue Components

No changes — same components used: `DataTable`, `Column`, `Button`, `Dialog`, `InputText`, `Tag`, `SelectButton`, `IconField`, `Card`, `ConfirmDialog`. MeasurementUnits adds an `abbreviation` column and a second `InputText` in the ItemEditor.

## Gotchas
1. **No `fetchApi` calls.** All data comes from Inertia props. Use `router.visit()` for server-side pagination/sorting/filtering — this reloads the page props without a full page refresh.
 2. **`preserveState: true`** on `router.visit()` keeps dialog state and local refs intact during pagination/filtering.
 3. **Form errors auto-shared.** Failed FormRequests populate `form.errors.field` via `setErrors()` — no manual error parsing needed.
 4. **Permissions via `v-can`.** Current `v-can="'brand.create"`` pattern stays the same. 5. **Flash errors from backend.** Controller `->with('error', ...)` on delete guard failure if available as `$page.props.flash.error` — show via toast. 6. **VeeValidate `setErrors()` may conflict with Inertia server errors.** After server-side validation fails, Inertia returns errors as page props. To avoid showing duplicate errors, call `form.resetForm()` inside the `onSuccess` callback or or reload.
 page props to Inertia.
 If using `useForm` directly for submission, pass the `onError` callback to call `setErrors()` manually from the Inertia error bag.
