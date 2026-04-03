# Settings — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Settings/
└── Index.vue        // Main settings page with tabbed groups
```

### Shared Components
```
resources/js/Components/Settings/
├── GeneralSettingsForm.vue    // Business name, address, phone, timezone
└── TaxSettingsForm.vue        // Tax rate input
```

---

## Index Page (`Settings/Index.vue`)

### Inertia Props
```vue
<script setup lang="ts">
import { ref } from 'vue'
import type { SettingGrouped } from '@/Types/setting-types'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  settings: SettingGrouped
  groups: string[]
}>()

const activeTab = ref(0)
</script>
```

### PrimeVue Components
| Component | Usage |
|---|---|
| `TabView` | Top-level tabs: General, Tax |
| `TabPanel` | One panel per group |
| `InputText` | Text settings (business name, address, phone) |
| `Dropdown` | Timezone selector |
| `InputNumber` | Tax rate |
| `Button` | Save per group with loading state |
| `Toast` | Success/error feedback |

### Tab Layout
```vue
<template>
  <AppLayout :title="t('Settings')">
    <TabView v-model:activeIndex="activeTab">
      <TabPanel :header="t('General')">
        <GeneralSettingsForm :settings="settings.general" />
      </TabPanel>

      <TabPanel :header="t('Tax')">
        <TaxSettingsForm :settings="settings.tax" />
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
import { toTypedSchema } from '@vee-validate/yup'
import { object, string } from 'yup'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { FloatLabel, InputText, Dropdown, Button, InlineMessage } from 'primevue'
import { timezone_identifiers_list } from '@/Composables/useTimezones'

const { t } = useI18n()
const toast = useToast()

const props = defineProps<{
  settings: { business_name: string; business_address: string; business_phone: string; timezone: string }
}>()

const schema = toTypedSchema(
  object({
    business_name: string().required().max(100),
    business_address: string().max(500).default(''),
    business_phone: string().max(30).default(''),
    timezone: string().required(),
  })
)

const { handleSubmit, errors, defineField, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    business_name: props.settings.business_name,
    business_address: props.settings.business_address ?? '',
    business_phone: props.settings.business_phone ?? '',
    timezone: props.settings.timezone,
  },
})

const [businessName, businessNameAttrs] = defineField('business_name')
const [businessAddress, businessAddressAttrs] = defineField('business_address')
const [businessPhone, businessPhoneAttrs] = defineField('business_phone')
const [timezone, timezoneAttrs] = defineField('timezone')

const onSubmit = handleSubmit((values) => {
  router.put(route('settings.general.update'), values, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: t('Saved'), detail: t('Settings updated successfully'), life: 3000 })
    },
    onError: (errors) => {
      setErrors(errors)
      toast.add({ severity: 'error', summary: t('Error'), detail: t('An unexpected error occurred.'), life: 3000 })
    },
  })
})
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-4 max-w-lg">
    <FloatLabel>
      <InputText id="business_name" v-model="businessName" v-bind="businessNameAttrs" class="w-full" />
      <label for="business_name">{{ t('Business Name') }}</label>
    </FloatLabel>
    <InlineMessage v-if="errors.business_name" severity="error">{{ errors.business_name }}</InlineMessage>

    <FloatLabel>
      <InputText id="business_address" v-model="businessAddress" v-bind="businessAddressAttrs" class="w-full" />
      <label for="business_address">{{ t('Business Address') }}</label>
    </FloatLabel>

    <FloatLabel>
      <InputText id="business_phone" v-model="businessPhone" v-bind="businessPhoneAttrs" class="w-full" />
      <label for="business_phone">{{ t('Business Phone') }}</label>
    </FloatLabel>

    <FloatLabel>
      <Dropdown
        id="timezone"
        v-model="timezone"
        v-bind="timezoneAttrs"
        :options="timezones"
        class="w-full"
        filter
      />
      <label for="timezone">{{ t('Timezone') }}</label>
    </FloatLabel>
    <InlineMessage v-if="errors.timezone" severity="error">{{ errors.timezone }}</InlineMessage>

    <Button type="submit" :label="t('Save')" icon="fa fa-save" :loading="form.processing" />
  </form>
</template>
```

---

## Tax Settings Form (`TaxSettingsForm.vue`)

```vue
<script setup lang="ts">
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/yup'
import { object, number } from 'yup'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'
import { InputNumber, Button, InlineMessage } from 'primevue'

const { t } = useI18n()
const toast = useToast()

const props = defineProps<{
  settings: { tax_rate: string }
}>()

const schema = toTypedSchema(
  object({
    tax_rate: number().required().min(0).max(100),
  })
)

const { handleSubmit, errors, defineField, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    tax_rate: parseFloat(props.settings.tax_rate) || 0,
  },
})

const [taxRate, taxRateAttrs] = defineField('tax_rate')

const onSubmit = handleSubmit((values) => {
  router.put(route('settings.tax.update'), values, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: t('Saved'), detail: t('Settings updated successfully'), life: 3000 })
    },
    onError: (errors) => {
      setErrors(errors)
      toast.add({ severity: 'error', summary: t('Error'), detail: t('An unexpected error occurred.'), life: 3000 })
    },
  })
})
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-4 max-w-xs">
    <div class="flex flex-col gap-1">
      <label for="tax_rate">{{ t('Tax Rate (%)') }}</label>
      <InputNumber
        id="tax_rate"
        v-model="taxRate"
        v-bind="taxRateAttrs"
        :min="0"
        :max="100"
        :min-fraction-digits="0"
        :max-fraction-digits="2"
        suffix=" %"
        class="w-full"
      />
      <InlineMessage v-if="errors.tax_rate" severity="error">{{ errors.tax_rate }}</InlineMessage>
    </div>

    <Button type="submit" :label="t('Save')" icon="fa fa-save" />
  </form>
</template>
```

---

## TypeScript Types

```
resources/js/Types/setting-types.ts
```

Already exists with the correct structure:
```typescript
export interface SettingGrouped {
  [group: string]: Record<string, string>;
}
```

---

## Shared Inertia Props

Settings are already shared globally via `HandleInertiaRequests` middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
$settingGroups = Cache::rememberForever('settings', function () {
    return Setting::all()->groupBy('group')->toArray();
});
$formattedSettings = [];
foreach ($settingGroups as $group => $settings) {
    foreach ($settings as $setting) {
        $formattedSettings[$group][$setting['key']] = $setting['value'];
    }
}
// Shared as: auth.settings.general.business_name, auth.settings.tax.tax_rate, etc.
```

Access in any component:
```typescript
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

## Good Practices
- Parse string values from Inertia props into their proper types before binding to form fields (`parseFloat`)
- Use `preserveScroll: true` so the page does not scroll to top after saving
- Show a `Toast` on success/error instead of a full page redirect
- Use `InputNumber` (not `InputText`) for numeric fields to enforce type at the UI level
- Use `useI18n()` for all user-visible text
- Use `@/`, `@components/`, `@composables/` path aliases for imports
- Use `defineOptions({ layout: AppLayout })` for layout
