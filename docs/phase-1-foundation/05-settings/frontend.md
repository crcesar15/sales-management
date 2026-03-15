# Settings — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Settings/
├── Index.vue        // Main settings page with tabbed groups
└── Permissions.vue  // (see 03-roles-and-permissions docs)
```

### Shared Components
```
resources/js/Components/Settings/
├── GeneralSettingsForm.vue    // Store name, address, phone, timezone
├── TaxSettingsForm.vue        // Tax rate input
├── ReceiptSettingsForm.vue    // Receipt header, footer, show_logo toggle
└── InventorySettingsForm.vue  // Low stock threshold, expiry alert days
```

---

## Index Page (`Settings/Index.vue`)

### Inertia Props
```vue
<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  settings: {
    general:   { store_name: string; store_address: string; store_phone: string; timezone: string }
    tax:       { tax_rate: string }
    receipt:   { receipt_header: string; receipt_footer: string; show_logo: string }
    inventory: { low_stock_default_threshold: string; expiry_alert_days: string }
  }
  groups: string[]
}>()

const activeTab = ref(0)
</script>
```

### PrimeVue Components
| Component | Usage |
|---|---|
| `TabView` | Top-level tabs: General, Tax, Receipt, Inventory |
| `TabPanel` | One panel per group |
| `InputText` | Text settings (store name, address, phone) |
| `Dropdown` | Timezone selector |
| `InputNumber` | Tax rate, thresholds |
| `InputSwitch` | Toggle for `show_logo` |
| `Textarea` | Receipt header and footer |
| `Button` | Save per group with loading state |
| `Toast` | Success/error feedback |

### Tab Layout
```vue
<template>
  <AppLayout title="Settings">
    <TabView>
      <TabPanel header="General">
        <GeneralSettingsForm :settings="settings.general" />
      </TabPanel>

      <TabPanel header="Tax">
        <TaxSettingsForm :settings="settings.tax" />
      </TabPanel>

      <TabPanel header="Receipt">
        <ReceiptSettingsForm :settings="settings.receipt" />
      </TabPanel>

      <TabPanel header="Inventory">
        <InventorySettingsForm :settings="settings.inventory" />
      </TabPanel>
    </TabView>
  </AppLayout>
</template>
```

---

## General Settings Form (`GeneralSettingsForm.vue`)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  settings: { store_name: string; store_address: string; store_phone: string; timezone: string }
}>()

const toast = useToast()

const form = useForm({
  store_name:    props.settings.store_name,
  store_address: props.settings.store_address,
  store_phone:   props.settings.store_phone,
  timezone:      props.settings.timezone,
})

function save() {
  form.put(route('settings.general.update'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Saved', detail: 'General settings updated.', life: 3000 })
    },
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 max-w-lg">
    <FloatLabel>
      <InputText id="store_name" v-model="form.store_name" class="w-full" />
      <label for="store_name">Store Name</label>
    </FloatLabel>
    <InlineMessage v-if="form.errors.store_name" severity="error">{{ form.errors.store_name }}</InlineMessage>

    <FloatLabel>
      <InputText id="store_address" v-model="form.store_address" class="w-full" />
      <label for="store_address">Store Address</label>
    </FloatLabel>

    <FloatLabel>
      <InputText id="store_phone" v-model="form.store_phone" class="w-full" />
      <label for="store_phone">Phone Number</label>
    </FloatLabel>

    <FloatLabel>
      <Dropdown
        id="timezone"
        v-model="form.timezone"
        :options="timezones"
        class="w-full"
        filter
      />
      <label for="timezone">Timezone</label>
    </FloatLabel>
    <InlineMessage v-if="form.errors.timezone" severity="error">{{ form.errors.timezone }}</InlineMessage>

    <Button
      type="submit"
      label="Save General Settings"
      icon="pi pi-save"
      :loading="form.processing"
    />
  </form>
</template>
```

---

## Tax Settings Form (`TaxSettingsForm.vue`)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  settings: { tax_rate: string }
}>()

const toast = useToast()

const form = useForm({
  tax_rate: parseFloat(props.settings.tax_rate),
})

function save() {
  form.put(route('settings.tax.update'), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Tax settings updated.', life: 3000 }),
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 max-w-xs">
    <div class="flex flex-col gap-1">
      <label for="tax_rate">Tax Rate (%)</label>
      <InputNumber
        id="tax_rate"
        v-model="form.tax_rate"
        :min="0"
        :max="100"
        :minFractionDigits="0"
        :maxFractionDigits="2"
        suffix=" %"
        class="w-full"
      />
      <InlineMessage v-if="form.errors.tax_rate" severity="error">{{ form.errors.tax_rate }}</InlineMessage>
    </div>

    <Button type="submit" label="Save Tax Settings" icon="pi pi-save" :loading="form.processing" />
  </form>
</template>
```

---

## Receipt Settings Form (`ReceiptSettingsForm.vue`)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  settings: { receipt_header: string; receipt_footer: string; show_logo: string }
}>()

const toast = useToast()

const form = useForm({
  receipt_header: props.settings.receipt_header,
  receipt_footer: props.settings.receipt_footer,
  show_logo:      props.settings.show_logo === 'true',
})

function save() {
  form.put(route('settings.receipt.update'), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Receipt settings updated.', life: 3000 }),
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 max-w-lg">
    <div class="flex flex-col gap-1">
      <label for="receipt_header">Receipt Header</label>
      <Textarea id="receipt_header" v-model="form.receipt_header" rows="3" class="w-full" />
    </div>

    <div class="flex flex-col gap-1">
      <label for="receipt_footer">Receipt Footer</label>
      <Textarea id="receipt_footer" v-model="form.receipt_footer" rows="3" class="w-full" />
    </div>

    <div class="flex items-center gap-3">
      <InputSwitch v-model="form.show_logo" inputId="show_logo" />
      <label for="show_logo">Show Store Logo on Receipt</label>
    </div>

    <Button type="submit" label="Save Receipt Settings" icon="pi pi-save" :loading="form.processing" />
  </form>
</template>
```

---

## Inventory Settings Form (`InventorySettingsForm.vue`)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  settings: { low_stock_default_threshold: string; expiry_alert_days: string }
}>()

const toast = useToast()

const form = useForm({
  low_stock_default_threshold: parseInt(props.settings.low_stock_default_threshold),
  expiry_alert_days:           parseInt(props.settings.expiry_alert_days),
})

function save() {
  form.put(route('settings.inventory.update'), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Inventory settings updated.', life: 3000 }),
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 max-w-xs">
    <div class="flex flex-col gap-1">
      <label for="low_stock">Low Stock Default Threshold</label>
      <InputNumber id="low_stock" v-model="form.low_stock_default_threshold" :min="1" :max="9999" class="w-full" />
      <small class="text-gray-500">Products with stock at or below this number will trigger a low stock alert.</small>
      <InlineMessage v-if="form.errors.low_stock_default_threshold" severity="error">{{ form.errors.low_stock_default_threshold }}</InlineMessage>
    </div>

    <div class="flex flex-col gap-1">
      <label for="expiry_days">Expiry Alert Days</label>
      <InputNumber id="expiry_days" v-model="form.expiry_alert_days" :min="1" :max="365" class="w-full" />
      <small class="text-gray-500">Alert when a product expires within this many days.</small>
      <InlineMessage v-if="form.errors.expiry_alert_days" severity="error">{{ form.errors.expiry_alert_days }}</InlineMessage>
    </div>

    <Button type="submit" label="Save Inventory Settings" icon="pi pi-save" :loading="form.processing" />
  </form>
</template>
```

---

## Pinia Store — `useSettingsStore`

Settings that are consumed globally (e.g., store name in the navbar, timezone for date formatting) should be stored in Pinia, populated from Inertia shared props.

```
resources/js/stores/settingsStore.ts
```

```typescript
import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export const useSettingsStore = defineStore('settings', () => {
  const page = usePage()

  const storeName = computed<string>(
    () => page.props.settings?.store_name ?? 'Sales Management'
  )

  const timezone = computed<string>(
    () => page.props.settings?.timezone ?? 'UTC'
  )

  const taxRate = computed<number>(
    () => parseFloat(page.props.settings?.tax_rate ?? '0')
  )

  const showLogo = computed<boolean>(
    () => page.props.settings?.show_logo === 'true'
  )

  return { storeName, timezone, taxRate, showLogo }
})
```

Share `settings` in the Inertia `HandleInertiaRequests` middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
'settings' => [
    'store_name' => Setting::get('store_name'),
    'timezone'   => Setting::get('timezone'),
    'tax_rate'   => Setting::get('tax_rate'),
    'show_logo'  => Setting::get('show_logo') ? 'true' : 'false',
],
```

---

## Ziggy Routes
```js
route('settings.index')             // GET /settings
route('settings.general.update')    // PUT /settings/general
route('settings.tax.update')        // PUT /settings/tax
route('settings.receipt.update')    // PUT /settings/receipt
route('settings.inventory.update')  // PUT /settings/inventory
route('settings.public')            // GET /settings/public
```

---

## Good Practices
- Parse string values from Inertia props into their proper types before binding to form fields (`parseInt`, `parseFloat`, `=== 'true'`)
- Use `preserveScroll: true` so the page does not scroll to top after saving a single tab
- Show a `Toast` on success instead of a full page redirect — settings pages benefit from immediate in-place feedback
- Use `InputNumber` (not `InputText`) for numeric fields to enforce type at the UI level
- Use `InputSwitch` for boolean settings like `show_logo` — avoid using a checkbox for a single toggle
- Share minimal settings (store_name, timezone, tax_rate) in Inertia shared props for global access; full settings detail only on the settings page itself
