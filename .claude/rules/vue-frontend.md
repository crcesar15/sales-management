# Vue Frontend Rules

## Page Structure

- Every page uses `<script setup lang="ts">` with Composition API
- Set layout via `defineOptions({ layout: AppLayout })`
- Module pages follow this directory structure:
  ```
  resources/js/Pages/{Module}/
  ├── Index.vue              # List view with DataTable
  ├── Create/
  │   └── Index.vue          # Create form
  ├── Edit/
  │   └── Index.vue          # Edit form
  ├── Show/
  │   └── Index.vue          # Detail/show view
  └── Components/
      └── ModuleComponent.vue # Page-local components
  ```
- Page-local components live alongside their pages under `Pages/{Module}/Components/` — there is no shared `Components/` directory

## Layouts

- Two layouts: `AppLayout` (admin, sidebar-based) from `resources/js/Layouts/admin.vue` and `PosLayout` (POS, shift bar) from `resources/js/Layouts/pos.vue`
- Set layout via `defineOptions({ layout: AppLayout })` or `defineOptions({ layout: PosLayout })`
- Login pages use a **separate Vue app** (`resources/js/login/index.js`) with Noir (zinc-based) theme, Options API, no i18n/Inertia/Pinia
- `HandleInertiaRequests` middleware shares `auth.user` (with roles, permissions), `auth.settings` (grouped by category), and `alertsSummary` (stock alert counts)

## Imports

- Use path aliases for all imports:
  - `@/` → `resources/js/`
  - `@components/` → `resources/js/Components/`
  - `@composables/` → `resources/js/Composables/`
  - `@app-types/` → `resources/js/Types/`
- Use `type` keyword for type-only imports:
  ```typescript
  import { type UserResponse } from "@/Types/user-types";
  ```

## Inertia Props

- Strongly type all props with TypeScript interfaces
- Include pagination meta shape:

```typescript
const props = defineProps<{
  users: {
    data: UserResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: Record<string, any>;
}>();
```

- `global.d.ts` augments Inertia's `PageProps` with `auth`, `settings`, and `appConfig`

## Form Handling

- Use VeeValidate + Yup for create/edit forms (NOT Inertia's `useForm`)
- Define schema with `toTypedSchema()`:

```typescript
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";

const schema = toTypedSchema(
  object({
    first_name: string().required().max(50),
    email: string().required().email(),
  })
);

const { handleSubmit, errors, defineField, setErrors } = useForm({
  validationSchema: schema,
  initialValues: { first_name: "" },
});

const [firstName, firstNameAttrs] = defineField("first_name");
```

- Submit via Inertia `router.post()` or `router.put()` with `onSuccess`/`onError` callbacks
- On error, show toast notification and call `setErrors()` to display field errors, then focus first invalid field:
  ```typescript
  onError: (errs) => {
    setErrors(errs);
    toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
    nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus());
  },
  ```
- Inertia `useForm` is ONLY used for delete/restore operations with an empty body:
  ```typescript
  const form = useForm({});
  form.delete(route("brands.destroy", brand.id));
  ```

## API Calls

- Two composable patterns for different use cases:
  - **Axios-based** (`useXxxClient`): for API calls that return data (search, autocomplete, async fetch). These wrap `useApi()` which provides an axios client with loading state.
  - **Inertia `router`**: for form mutations and server-side page navigation. Use `router.visit()` for list page pagination/filtering with `preserveState` and `replace`.

```typescript
// Axios-based composable (for data fetching)
const { loading, fetchProductsApi } = useProductClient();

// Inertia router (for form submissions and navigation)
router.visit(route("users"), {
  data: { filter: val, status: status.value },
  preserveState: true,
  replace: true,
});
```

## Composables

### `useAuth()`
- `can(permission)`, `canAny(permissions[])`, `canAll(permissions[])` — permission checks
- `getSetting(group, key, defaultValue?)` — read cached application settings
- `userFullName` — computed full name from first_name + last_name
- `isAuthenticated`, `isGuest` — auth state

### `useCurrencyFormatter()`
- `formatCurrency(value)` — formats with currency code (e.g., "BOB 100.00")
- `formatCurrencySymbol(value)` — formats with currency symbol
- Reads settings via `useAuth().getSetting()` for currency/precision

### `useDatetimeFormatter()`
- Date/time formatting driven by application settings (timezone, format)

### Module-level Refs
- `useLayout()` and `usePosLayout()` use module-level refs (variables defined outside the composable function body) for shared state across component instances
- This pattern is used instead of Pinia for admin pages

## State Management

- **Pinia** is only used for the POS module (`usePosStore` with Composition API style)
- Admin pages rely on Inertia props and composables for state
- Module-level shared state uses module-level refs (not Pinia stores)

## PrimeVue Components

- Import components directly from `primevue`:
  ```typescript
  import { DataTable, Column, Button, InputText } from "primevue";
  ```
- Use `lazy` mode on DataTable for server-side pagination
- Handle page/sort events with `@page` and `@sort` handlers
- PrimeVue Aura theme with custom blue primary palette (`#00539b`), dark mode via `["class", ".app-dark"]` dual trigger
- `ToastService` and `ConfirmationService` registered globally in `app.ts`

## Notifications

### Toast

```typescript
const toast = useToast();
toast.add({ severity: "success", summary: t("Success"), detail: t("Brand deleted successfully"), life: 3000 });
```

Severity values: `"success"`, `"error"`, `"warn"`, `"info"`. Life defaults to 3000ms.

### Confirm Dialog

```typescript
const confirm = useConfirm();
confirm.require({
  message: t("Are you sure?"),
  header: t("Confirmation"),
  icon: "fa fa-triangle-exclamation",
  accept: () => { /* action */ },
});
```

## v-can Directive

- `v-can="'brand.create'"` — single permission check
- `v-can="['brand.create', 'brand.edit']"` — any permission (canAny)
- `v-can="true"` — always show (useful for debugging)
- The directive is **reactive** — watches permissions from `useAuth()` and dynamically shows/hides elements
- Registered in `app.ts` as a custom directive from `resources/js/Directives/can.ts`

## Internationalization

- Always use `t()` from `vue-i18n` for user-visible text:
  ```typescript
  const { t } = useI18n();
  ```
- Translation keys are in `resources/lang/en.json` and `resources/lang/es.json`
- Use plain English strings as keys (e.g., `t('Users')`, `t('Add User')`)
- Default locale is Spanish (`es`), fallback is also `es`
- Yup locale is tied to vue-i18n via `configureYupLocale()` called in `app.ts`

## TypeScript Types

- Located in `resources/js/Types/` with one file per domain
- Naming convention: `{Entity}`, `{Entity}Response`, `{Entity}Payload`
- Response types extend base types and add `id`, timestamps, relations
- Always export as named exports:
  ```typescript
  export interface UserResponse extends User {
    id: number
    full_name: string
    created_at: string
    updated_at: string
    roles: RoleResponse[]
  }
  ```
- `global.d.ts` augments Inertia `PageProps` with `auth`, `settings`, `appConfig`
- ModelTyper (`fumeapp/modeltyper`) generates TypeScript types from Eloquent models

## Image Uploads

- Two-phase upload pattern:
  1. Upload to `PendingMediaUpload` via `useMediaClient` composable (returns temp ID)
  2. Commit to product via `PendingMediaService::commit()` when form is saved
- `vue-advanced-cropper` for image cropping
- `CustomPathGeneratorService` hashes media paths using `md5($media->id . config('app.key'))`

## Dark Mode

- Dual trigger: `["class", ".app-dark"]` — toggles via both class and CSS selector
- PrimeVue Aura theme with custom blue primary palette (`#00539b`)
- Login app uses Noir (zinc-based) theme

## Styling

- Font Awesome 6 for icons (`fa fa-xxx` class prefix)
- PrimeVue components imported directly from `primevue`
- `moment-timezone` for date handling (via `useDatetimeFormatter`)
- Grid layout: `grid grid-cols-12 gap-4` with responsive columns (`md:col-span-6 lg:col-span-4`)
- Validation errors: `text-red-400 dark:text-red-300`, `p-invalid` class on inputs
- Secondary text: `text-surface-500 dark:text-surface-400`
- Backgrounds: `bg-surface-50 dark:bg-surface-950`
- Borders: `border-surface-200 dark:border-surface-700`
- Primary buttons: `uppercase` class and `raised` attribute