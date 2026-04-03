# Vue Frontend Rules

## Page Structure

- Every page uses `<script setup lang="ts">` with Composition API
- Set layout via `defineOptions({ layout: AppLayout })`
- Module pages follow this directory structure:
  ```
  resources/js/Pages/{Module}/
  ├── Index.vue          # List view with DataTable
  ├── Create/
  │   └── Index.vue      # Create form
  └── Edit/
      └── Index.vue      # Edit form
  ```

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

## Form Handling

- Use VeeValidate + Yup for form validation (not Inertia's `useForm`)
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
- On error, show toast notification and call `setErrors()` to display field errors

## API Calls

- Use module-specific composables (e.g., `useUserClient`, `useProductClient`) for API calls
- These wrap the shared `useApi()` composable which provides an axios client with loading state
- Use `router.visit()` for server-side pagination/sorting/filtering on list pages:

```typescript
router.visit(route("users"), {
  data: { filter: val, status: status.value },
  preserveState: true,
  replace: true,
});
```

## PrimeVue Components

- Import components directly from `primevue`:
  ```typescript
  import { DataTable, Column, Button, InputText } from "primevue";
  ```
- Use `lazy` mode on DataTable for server-side pagination
- Handle page/sort events with `@page` and `@sort` handlers

## Internationalization

- Always use `t()` from `vue-i18n` for user-visible text:
  ```typescript
  const { t } = useI18n();
  ```
- Translation keys are in `resources/lang/en.json` and `resources/lang/es.json`
- Use plain English strings as keys (e.g., `t('Users')`, `t('Add User')`)

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
