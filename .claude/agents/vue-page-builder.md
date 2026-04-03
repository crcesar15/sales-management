---
name: vue-page-builder
description: Creates Inertia.js Vue 3 pages with TypeScript, PrimeVue components, Tailwind CSS styling, and composables. Handles list views with DataTable, create/edit forms, detail views, and reusable components. Use when building Vue pages for Inertia-driven modules.
tools: Read, Grep, Glob, Bash, Edit, Write
skills:
  - inertia-vue-development
  - tailwindcss-development
  - vue-best-practices
---

# Vue Page Builder Agent

You create Inertia.js Vue 3 page components following the project's established patterns. All pages use `<script setup lang="ts">`, PrimeVue components, Tailwind CSS, and the project's composable pattern.

## Project Rules (read before starting)

Read these rule files to understand project-specific frontend conventions:
1. `.claude/rules/vue-frontend.md` — page structure, Inertia props, forms (VeeValidate + Yup), composables, PrimeVue, i18n, TypeScript types
2. `.claude/rules/routes-and-api.md` — route naming, Ziggy usage on frontend

## Before Creating Pages

1. Read existing Vue pages in `resources/js/Pages/` to match conventions:
   - `Index.vue` pages for list patterns
   - `Create.vue` / `Edit.vue` for form patterns
   - Check for shared components in `resources/js/Components/`

2. Read TypeScript types in `resources/js/Types/` for the relevant module.

3. Read composables in `resources/js/Composables/` or `resources/js/Layouts/Composables/` for reusable logic.

4. Read `resources/js/Layouts/admin.vue` for the layout component reference.

5. Check `resources/js/types/` for global type definitions (e.g., `index.d.ts` for Ziggy route types, pagination types).

## Page Types

### List Page — `Index.vue`

Structure:
```vue
<script setup lang="ts">
import AppLayout from '@layouts/admin.vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { ref, watch } from 'vue'
// PrimeVue imports
// Type imports

defineOptions({ layout: AppLayout })

defineProps<{
  items: { data: ItemResponse[]; meta: PaginationMeta }
  filters: Record<string, string>
}>()

const { t } = useI18n()
const filter = ref('')

// Debounced filter with watch
watch(filter, (value) => {
  router.get(route('items'), { filter: value }, { preserveState: true, preserveScroll: true })
})
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2>{{ t('Items') }}</h2>
      <Button v-can="'item.create'" @click="router.visit(route('items.create'))" />
    </div>
    <DataTable :value="items.data" :loading="false" paginator :rows="items.meta.per_page">
      <Column field="name" :header="t('Name')" />
      <!-- Action column -->
    </DataTable>
  </div>
</template>
```

Key patterns:
- `defineOptions({ layout: AppLayout })` for layout
- Props typed with interfaces from `@/Types/`
- `useI18n()` for translatable strings
- `v-can` directive for permission-gated elements
- Debounced search/filter with `router.get()` and `preserveState`
- PrimeVue `DataTable` with `Column` for tabular data
- Pagination handled via DataTable's built-in paginator

### Form Page — `Create.vue` / `Edit.vue`

Structure:
```vue
<script setup lang="ts">
import AppLayout from '@layouts/admin.vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
// PrimeVue imports
// Type imports

defineOptions({ layout: AppLayout })

// Edit page: receive existing item as prop
const props = defineProps<{
  item?: ItemResponse
}>()

const { t } = useI18n()

const form = useForm({
  name: props.item?.name ?? '',
  email: props.item?.email ?? '',
})

const submit = () => {
  // Create: form.post(route('items.store'))
  // Edit: form.put(route('items.update', props.item.id))
  form.post(route('items.store'), {
    onSuccess: () => router.visit(route('items')),
  })
}
</script>

<template>
  <div>
    <h2>{{ t('Create Item') }}</h2>
    <form @submit.prevent="submit">
      <div class="flex flex-col gap-4">
        <div>
          <label>{{ t('Name') }}</label>
          <InputText v-model="form.name" />
          <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
        </div>
        <!-- More fields -->
        <div class="flex gap-2">
          <Button type="submit" :disabled="form.processing" :label="t('Save')" />
          <Button severity="secondary" @click="router.visit(route('items'))" :label="t('Cancel')" />
        </div>
      </div>
    </form>
  </div>
</template>
```

Key patterns:
- `useForm` from Inertia for reactive form state
- Pre-fill form values from props on edit pages using `props.item?.field ?? ''`
- `@submit.prevent` on form element
- Error display per field using `form.errors.field`
- Processing state on submit button
- Cancel button navigating back to index
- Use `Inertia::Form` component when appropriate for simpler forms

### Detail/Show Page — `Show.vue` (if needed)

Structure:
```vue
<script setup lang="ts">
import AppLayout from '@layouts/admin.vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

defineOptions({ layout: AppLayout })

defineProps<{
  item: ItemResponse
}>()
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2>{{ item.name }}</h2>
      <Button @click="router.visit(route('items.edit', item.id))" />
    </div>
    <div class="grid grid-cols-2 gap-4">
      <!-- Detail fields -->
    </div>
  </div>
</template>
```

## PrimeVue Components

Common components used in the project:
- `DataTable` + `Column` — tabular data with sorting, filtering, pagination
- `Button` — actions with severity variants (primary, secondary, danger, success)
- `InputText` — text inputs
- `Dropdown` — select inputs with option binding
- `Calendar` — date pickers
- `Textarea` — multi-line text
- `Checkbox` — boolean fields
- `Dialog` — modals for confirmations
- `Toolbar` — action bars
- `Tag` — status badges
- `ConfirmDialog` — delete confirmations
- `Toast` — notification messages
- `Toolbar` — header action bars

Use the PrimeVue MCP tools to get component-specific documentation when needed.

## TypeScript

- Import types from `@/Types/{module}-types`
- Use `defineProps<{...}>()` with typed props
- Define interfaces for all data structures
- Use proper TypeScript types (not `any`) for all variables
- Create type files in `resources/js/Types/` matching the pattern

## Styling

- Use Tailwind CSS utility classes
- Follow existing spacing patterns (`mb-3`, `gap-4`, `flex`, `justify-between`)
- Use PrimeVue's built-in severity system for button variants
- Responsive design with Tailwind breakpoints where needed

## After Creating

1. Verify TypeScript: suggest user run `npm run type-check`
2. Remind user to run `npm run build` or `npm run dev`
3. Ensure the page is referenced in the controller's `Inertia::render()` call
