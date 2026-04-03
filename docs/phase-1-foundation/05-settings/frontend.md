# Settings — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Settings/
└── Index.vue        // Main settings page with tabbed groups
```

No separate form components — with only 5 fields across 2 tabs, forms are inlined in `Index.vue`.

---

## Index Page (`Settings/Index.vue`)

### Inertia Props
```typescript
const props = defineProps<{
  settings: Record<string, Record<string, string>>
  groups: string[]
}>()
```

### PrimeVue Components
| Component | Usage |
|---|---|
| `TabView` | Top-level tabs: General, Tax |
| `TabPanel` | One panel per group |
| `InputText` | Text settings (business name, address, phone) |
| `Select` | Timezone selector (filterable) |
| `InputNumber` | Tax rate with suffix " %" |
| `Button` | Save per group with loading state |
| `Card` | Wraps the tab content |

### Tab Layout
```vue
<template>
  <div>
    <h4>{{ t("Settings") }}</h4>
    <Card>
      <template #content>
        <TabView>
          <TabPanel :header="t('General')">
            <!-- General form inline -->
          </TabPanel>
          <TabPanel :header="t('Tax')">
            <!-- Tax form inline -->
          </TabPanel>
        </TabView>
      </template>
    </Card>
  </div>
</template>
```

---

## General Tab

### Fields
| Field | Component | Validation | Notes |
|---|---|---|---|
| `business_name` | `InputText` | Required, max 100 | |
| `business_address` | `InputText` | Nullable, max 500 | Full width |
| `business_phone` | `InputText` | Nullable, max 30 | `type="tel"` |
| `timezone` | `Select` | Required | Filterable, options from `Intl.supportedValuesOf('timeZone')` |

### VeeValidate Schema
```typescript
const schema = toTypedSchema(
  object({
    business_name: string().required().max(100),
    business_address: string().nullable().optional().max(500),
    business_phone: string().nullable().optional().max(30),
    timezone: string().required(),
  })
)
```

### Submit
```typescript
router.put(route("settings.general.update"), values, {
  preserveScroll: true,
  onSuccess: () => toast.add({ severity: "success", ... }),
  onError: (errors) => { setErrors(errors); toast.add({ severity: "error", ... }) },
})
```

---

## Tax Tab

### Fields
| Field | Component | Validation | Notes |
|---|---|---|---|
| `tax_rate` | `InputNumber` | Required, min 0, max 100 | suffix " %", 2 decimal places |

### VeeValidate Schema
```typescript
const schema = toTypedSchema(
  object({
    tax_rate: number().required().min(0).max(100),
  })
)
```

### Submit
```typescript
router.put(route("settings.tax.update"), values, {
  preserveScroll: true,
  onSuccess: () => toast.add({ severity: "success", ... }),
  onError: (errors) => { setErrors(errors); toast.add({ severity: "error", ... }) },
})
```

---

## Input Type Selection per Field

| Field | Why this component |
|---|---|
| `business_name` | `InputText` — simple text input, single line |
| `business_address` | `InputText` — simple text input, single line (not textarea — addresses are typically short) |
| `business_phone` | `InputText` with `type="tel"` — triggers phone keyboard on mobile |
| `timezone` | `Select` (filterable) — controlled list from `Intl.supportedValuesOf('timeZone')`, not freeform |
| `tax_rate` | `InputNumber` — numeric-only input with min/max bounds, suffix " %" |

---

## TypeScript Types

Already defined in `resources/js/Types/setting-types.ts`:
```typescript
export interface SettingGrouped {
  [group: string]: Record<string, string>;
}
```

---

## Shared Inertia Props

Settings are shared globally via `HandleInertiaRequests` middleware:
```typescript
// Access in any component:
const page = usePage()
const businessName = computed(() => page.props.auth?.settings?.general?.business_name ?? 'My Store')
const taxRate = computed(() => parseFloat(page.props.auth?.settings?.tax?.tax_rate ?? '0'))
```

---

## Ziggy Routes
```typescript
route('settings')                  // GET /settings
route('settings.general.update')   // PUT /settings/general
route('settings.tax.update')       // PUT /settings/tax
```

---

## Design Notes
- **Labels**: Visible labels above inputs (not FloatLabel), — matches Stores/Users edit pattern
- **Layout**: Single `Card` wrapping `TabView`, no sidebar needed (only 5 fields)
- **Grid**: 12-column grid with `md:col-span-6` for side-by-side fields
- **Errors**: Red `small` text below invalid fields with `p-invalid` class on input
- **Submit**: Per-tab save buttons with `preserveScroll: true`, and toast feedback
- **Timezone options**: `Intl.supportedValuesOf('timeZone')` mapped to `{ label, value }` with underscores replacing underscores for display
