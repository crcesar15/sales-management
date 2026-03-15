# Roles & Permissions — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Settings/
└── Permissions.vue     // Permission assignment UI for the sales_rep role
```

### Shared Composables & Directives
```
resources/js/composables/
└── usePermissions.ts   // Composable to check permissions from Inertia shared props

resources/js/directives/
└── can.ts              // v-can directive for template-level permission checks
```

---

## Permissions Page (`Settings/Permissions.vue`)

### Inertia Props
```vue
<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps<{
  roles: Array<{ id: number; name: string }>
  allPermissions: Record<string, Array<{ id: number; name: string; category: string }>>
  salesRepPermissions: string[]
}>()

const selected = ref<string[]>([...props.salesRepPermissions])
const saving = ref(false)

const categories = computed(() => Object.keys(props.allPermissions))

function isSelected(permissionName: string): boolean {
  return selected.value.includes(permissionName)
}

function togglePermission(permissionName: string): void {
  if (isSelected(permissionName)) {
    selected.value = selected.value.filter(p => p !== permissionName)
  } else {
    selected.value.push(permissionName)
  }
}

function save(): void {
  saving.value = true
  router.put(
    route('roles.permissions.update', { role: 2 }), // sales_rep role id
    { permissions: selected.value },
    {
      onSuccess: () => { /* flash message shown via shared props */ },
      onFinish: () => { saving.value = false },
    }
  )
}
</script>
```

### PrimeVue Components
| Component | Usage |
|---|---|
| `Panel` | Groups permissions by category |
| `Checkbox` | Toggle individual permissions on/off |
| `Button` | Save changes with loading state |
| `Toast` | Success/error feedback after save |
| `Divider` | Separates category sections |
| `Tag` | Label showing role name |
| `Message` | Informational note about admin role being immutable |

### Template Structure
```vue
<template>
  <AppLayout title="Permissions">
    <div class="max-w-4xl mx-auto space-y-6">
      <Message severity="info">
        Admin role permissions are fixed and cannot be modified.
      </Message>

      <Panel
        v-for="category in categories"
        :key="category"
        :header="category"
        toggleable
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div
            v-for="permission in allPermissions[category]"
            :key="permission.name"
            class="flex items-center gap-2"
          >
            <Checkbox
              :modelValue="isSelected(permission.name)"
              :binary="true"
              :inputId="permission.name"
              @change="togglePermission(permission.name)"
            />
            <label :for="permission.name" class="cursor-pointer text-sm">
              {{ permission.name }}
            </label>
          </div>
        </div>
      </Panel>

      <div class="flex justify-end">
        <Button
          label="Save Permissions"
          icon="pi pi-save"
          :loading="saving"
          @click="save"
        />
      </div>
    </div>
  </AppLayout>
</template>
```

---

## `usePermissions` Composable

```
resources/js/composables/usePermissions.ts
```

```typescript
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
  const page = usePage()

  const permissions = computed<string[]>(
    () => page.props.auth?.user?.permissions ?? []
  )

  const roles = computed<string[]>(
    () => page.props.auth?.user?.roles ?? []
  )

  function can(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  function hasRole(role: string): boolean {
    return roles.value.includes(role)
  }

  const isAdmin = computed(() => hasRole('admin'))
  const isSalesRep = computed(() => hasRole('sales_rep'))

  return { can, hasRole, isAdmin, isSalesRep, permissions, roles }
}
```

**Usage in any component:**
```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions'

const { can, isAdmin } = usePermissions()
</script>

<template>
  <Button v-if="can('users.manage')" label="Manage Users" />
  <Button v-if="isAdmin" label="Admin Panel" />
</template>
```

---

## `v-can` Directive

```
resources/js/directives/can.ts
```

```typescript
import type { Directive } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const vCan: Directive<HTMLElement, string> = {
  mounted(el, binding) {
    const page = usePage()
    const permissions: string[] = page.props.auth?.user?.permissions ?? []

    if (!permissions.includes(binding.value)) {
      el.style.display = 'none'
    }
  },
  updated(el, binding) {
    const page = usePage()
    const permissions: string[] = page.props.auth?.user?.permissions ?? []

    el.style.display = permissions.includes(binding.value) ? '' : 'none'
  },
}
```

**Register globally in `app.ts`:**
```typescript
import { vCan } from './directives/can'

createInertiaApp({
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .directive('can', vCan)
      .mount(el)
  },
})
```

**Usage:**
```vue
<Button v-can="'users.manage'" label="Manage Users" />
<Link v-can="'reports.view_all'" :href="route('reports.index')">All Reports</Link>
```

---

## Pinia Store
No dedicated Pinia store needed for permissions. Permissions are available from Inertia's shared props via `usePage()` or the `usePermissions()` composable. The composable is reactive and updates automatically when shared props change.

---

## Ziggy Routes
```js
route('settings.permissions')          // GET /settings/permissions
route('roles.permissions', { role: 2 }) // GET /roles/2/permissions
route('roles.permissions.update', { role: 2 }) // PUT /roles/2/permissions
```

---

## Good Practices
- Always use `usePermissions()` composable instead of directly accessing `page.props.auth.user.permissions` — keeps the logic centralized
- Use `v-can` for simple show/hide; use `can()` inside `<script setup>` for conditional rendering of larger blocks
- Do not rely solely on frontend permission checks — backend policies must always be the authoritative source
- Pass all required data (roles, allPermissions, salesRepPermissions) as Inertia props from the controller to avoid extra API calls
- Show a clear `Message` component that the `admin` role is immutable to set user expectations
