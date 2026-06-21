<script setup lang="ts">
import {
  AutoComplete,
  Button,
  Column,
  ConfirmDialog,
  DataTable,
  Dialog,
  InputNumber,
  Message,
  Select,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  Tag,
  useConfirm,
  useToast,
} from "primevue";

import { router, useForm } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ref, computed, watch, nextTick } from "vue";
import { route } from "ziggy-js";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm as useVeeForm } from "vee-validate";
import { number, object, string } from "yup";
import type { InventoryVariantDetail, InventoryProductDetail, VariantUnitResource } from "@/Types/inventory-variant-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useAuth } from "@/Composables/useAuth";
import type { MeasurementUnitResponse } from "@/Types/measurement-unit-types";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  measurementUnits: Pick<MeasurementUnitResponse, "id" | "name" | "abbreviation">[];
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";

const activeTab = ref<"sale" | "purchase">("sale");
const dialogVisible = ref(false);
const editing = ref<VariantUnitResource | null>(null);
const isEditing = computed(() => editing.value !== null);

const saleUnits = computed(() => props.variant.sale_units ?? []);
const purchaseUnits = computed(() => props.variant.purchase_units ?? []);
const currentType = computed(() => activeTab.value);

const baseUnitName = computed(() => props.product.measurement_unit?.name ?? t("Unit"));
const baseUnitAbbr = computed(() => props.product.measurement_unit?.abbreviation ?? baseUnitName.value);

const allMeasurementUnits = computed(() => props.measurementUnits);
const filteredUnits = ref<Pick<MeasurementUnitResponse, "id" | "name" | "abbreviation">[]>([]);

const searchUnits = (event: { query: string }) => {
  const query = event.query.trim().toLowerCase();
  filteredUnits.value = query
    ? allMeasurementUnits.value.filter((u) => u.name.toLowerCase().includes(query) || u.abbreviation.toLowerCase().includes(query))
    : allMeasurementUnits.value;
};

interface BaseUnitRow {
  isBase: true;
  name: string;
  conversion_factor: number;
  price: string | null;
  status: string;
  type: "sale" | "purchase";
}

const saleUnitsWithBase = computed(() => {
  const base: BaseUnitRow = {
    isBase: true,
    name: `${baseUnitName.value} (${t("Base").toLowerCase()})`,
    conversion_factor: 1,
    price: String(props.variant.price),
    status: "base",
    type: "sale",
  };
  return [base, ...saleUnits.value];
});

const purchaseUnitsWithBase = computed(() => {
  const base: BaseUnitRow = {
    isBase: true,
    name: `${baseUnitName.value} (${t("Base").toLowerCase()})`,
    conversion_factor: 1,
    price: null,
    status: "base",
    type: "purchase",
  };
  return [base, ...purchaseUnits.value];
});

const statusOptions = computed(() => [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
]);

const statusLabel = (s: string) => (s === "active" ? t("Active") : t("Inactive"));

const conversionDisplay = (unit: { name: string; conversion_factor: number; isBase?: boolean }): string => {
  if (unit.conversion_factor === 1 && unit.isBase) {
    return baseUnitName.value;
  }
  return `1 ${unit.name} = ${unit.conversion_factor} ${baseUnitAbbr.value}`;
};

// VeeValidate + Yup schema for the create/edit dialog.
const schema = toTypedSchema(
  object({
    name: string().required().max(100),
    conversion_factor: number().required().integer().min(1),
    price: number()
      .nullable()
      .min(0)
      .when("type", {
        is: "sale",
        then: (s) => s.required(),
      }),
    status: string().required().oneOf(["active", "inactive"]),
    sort_order: number().nullable().integer().min(0),
  }),
);

const { handleSubmit, errors, defineField, setErrors, resetForm, validateField, submitCount } = useVeeForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    name: "",
    conversion_factor: 1,
    price: null,
    status: "active",
    sort_order: 0,
  },
});

// Hidden type field is tracked separately (not user-editable inside the dialog).
const formType = ref<"sale" | "purchase">("sale");
const setFormType = (value: "sale" | "purchase") => {
  formType.value = value;
};

const [nameField, nameAttrs] = defineField("name");
const [conversionField, conversionAttrs] = defineField("conversion_factor");
const [priceField, priceAttrs] = defineField("price");
const [statusField, statusAttrs] = defineField("status");
const [sortOrderField, sortOrderAttrs] = defineField("sort_order");

const saleUnitsCount = computed(() => saleUnits.value.length);
const purchaseUnitsCount = computed(() => purchaseUnits.value.length);

// Suggested price = base variant selling price × conversion factor.
const suggestedPrice = computed(() => {
  const factor = conversionField.value;
  if (!factor || factor <= 0) return null;
  return Math.round(props.variant.price * factor * 100) / 100;
});

const openCreate = () => {
  editing.value = null;
  setFormType(currentType.value);
  resetForm({
    values: {
      name: "",
      conversion_factor: 1,
      price: currentType.value === "sale" ? 0 : null,
      status: "active",
      sort_order: 0,
    },
  });
  filteredUnits.value = [];
  dialogVisible.value = true;
};

const openEdit = (unit: VariantUnitResource) => {
  editing.value = unit;
  setFormType(unit.type);
  resetForm({
    values: {
      name: unit.name,
      conversion_factor: unit.conversion_factor,
      price: unit.price,
      status: unit.status,
      sort_order: unit.sort_order,
    },
  });
  filteredUnits.value = [];
  dialogVisible.value = true;
};

const applySuggestedPrice = () => {
  if (suggestedPrice.value !== null) {
    priceField.value = suggestedPrice.value;
  }
};

const deleteForm = useForm({});

const onSubmit = handleSubmit(async (values) => {
  const payload: Record<string, unknown> = {
    type: formType.value,
    name: values.name,
    conversion_factor: values.conversion_factor,
    price: formType.value === "purchase" ? null : values.price,
    status: values.status,
    sort_order: values.sort_order ?? 0,
  };

  const onSuccess = () => {
    dialogVisible.value = false;
    toast.add({ severity: "success", summary: t("Success"), detail: isEditing.value ? t("Unit updated") : t("Unit created"), life: 3000 });
    router.reload({ only: ["variant"] });
  };

  const onError = (errs: Record<string, string>) => {
    setErrors(errs);
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(Object.values(errs)[0] ?? "An error occurred"),
      life: 3000,
    });
    nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus());
  };

  if (isEditing.value) {
    router.put(route("variant.units.update", [props.product.id, props.variant.id, editing.value!.id]), payload, { onSuccess, onError });
    return;
  }

  router.post(route("variant.units.store", [props.product.id, props.variant.id]), payload, {
    onSuccess,
    onError,
  });
});

const onDelete = (unit: VariantUnitResource) => {
  confirm.require({
    message: t("Are you sure you want to delete this unit?"),
    header: t("Confirm"),
    icon: "fa fa-triangle-exclamation",
    acceptLabel: t("Delete"),
    rejectLabel: t("Cancel"),
    rejectClass: "p-button-secondary",
    accept: () => {
      deleteForm.delete(route("variant.units.destroy", [props.product.id, props.variant.id, unit.id]), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Unit deleted"), life: 3000 });
          router.reload({ only: ["variant"] });
        },
        onError: (errs) => {
          toast.add({ severity: "error", summary: t("Error"), detail: t(Object.values(errs)[0] ?? "An error occurred"), life: 3000 });
        },
      });
    },
  });
};

watch(activeTab, () => {
  dialogVisible.value = false;
});

// Re-validate price when type toggles (sale requires price, purchase doesn't).
watch(formType, () => {
  if (submitCount.value > 0) {
    validateField("price");
  }
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <ConfirmDialog />

    <Tabs v-model:value="activeTab">
      <TabList
        :pt="{
          activeBar: {
            class: 'border-2 border-primary',
          },
        }"
      >
        <Tab
          value="sale"
        >
          <div class="flex items-center gap-2">
            <span>{{ t("Sale Units") }}</span>
            <Tag :value="saleUnitsCount" severity="secondary" rounded />
          </div>
        </Tab>
        <Tab
          value="purchase"
        >
          <div class="flex items-center gap-2">
            <span>{{ t("Purchase Units") }}</span>
            <Tag :value="purchaseUnitsCount" severity="secondary" rounded />
          </div>
        </Tab>
      </TabList>
      <TabPanels
        :pt="{
          root: {
            class: ['!m-0 !p-0'],
          },
        }"
      >
        <!-- Sale Units Tab -->
        <TabPanel value="sale">
          <div class="overflow-x-auto">
            <DataTable :value="saleUnitsWithBase">
              <Column :header="t('Name')">
                <template #body="{ data: unit }">
                  <span :class="{ 'italic text-surface-500 dark:text-surface-400': unit.isBase }">
                    {{ unit.name }}
                  </span>
                </template>
              </Column>
              <Column :header="t('Conversion Factor')">
                <template #body="{ data: unit }">
                  {{ conversionDisplay(unit) }}
                </template>
              </Column>
              <Column :header="t('Price')">
                <template #body="{ data: unit }">
                  {{ unit.price !== null && unit.price !== undefined ? formatCurrency(String(unit.price)) : "—" }}
                </template>
              </Column>
              <Column :header="t('Status')">
                <template #body="{ data: unit }">
                  <Tag v-if="unit.isBase" :value="t('Base')" severity="info" />
                  <Tag v-else :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
                </template>
              </Column>
              <Column :header="t('Actions')" style="width: 8rem">
                <template #body="{ data: unit }">
                  <template v-if="!unit.isBase">
                    <div class="flex gap-1">
                      <Button
                        v-can="'inventory.edit'"
                        icon="fa fa-pen"
                        text
                        rounded
                        v-tooltip.top="t('Edit')"
                        @click="openEdit(unit as VariantUnitResource)"
                      />
                      <Button
                        v-can="'inventory.edit'"
                        icon="fa fa-trash"
                        text
                        rounded
                        severity="danger"
                        v-tooltip.top="t('Delete')"
                        @click="onDelete(unit as VariantUnitResource)"
                      />
                    </div>
                  </template>
                  <span v-else class="text-surface-300 dark:text-surface-600">&mdash;</span>
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-8">
                  <i class="fa fa-box-open text-3xl text-surface-300 dark:text-surface-600 mb-2 block" />
                  <p class="text-surface-500">{{ t("No sale units defined") }}</p>
                </div>
              </template>
            </DataTable>
          </div>
          <div class="flex justify-end mt-3">
            <Button v-can="'inventory.edit'" :label="t('Add Sale Unit')" icon="fa fa-plus" @click="openCreate" />
          </div>
        </TabPanel>

        <!-- Purchase Units Tab -->
        <TabPanel value="purchase">
          <div class="overflow-x-auto">
            <DataTable :value="purchaseUnitsWithBase">
              <Column :header="t('Name')">
                <template #body="{ data: unit }">
                  <span :class="{ 'italic text-surface-500 dark:text-surface-400': unit.isBase }">
                    {{ unit.name }}
                  </span>
                </template>
              </Column>
              <Column :header="t('Conversion Factor')">
                <template #body="{ data: unit }">
                  {{ conversionDisplay(unit) }}
                </template>
              </Column>
              <Column :header="t('Status')">
                <template #body="{ data: unit }">
                  <Tag v-if="unit.isBase" :value="t('Base')" severity="info" />
                  <Tag v-else :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
                </template>
              </Column>
              <Column :header="t('Actions')" style="width: 8rem">
                <template #body="{ data: unit }">
                  <template v-if="!unit.isBase">
                    <div class="flex gap-1">
                      <Button
                        v-can="'inventory.edit'"
                        icon="fa fa-pen"
                        text
                        rounded
                        v-tooltip.top="t('Edit')"
                        @click="openEdit(unit as VariantUnitResource)"
                      />
                      <Button
                        v-can="'inventory.edit'"
                        icon="fa fa-trash"
                        text
                        rounded
                        severity="danger"
                        v-tooltip.top="t('Delete')"
                        @click="onDelete(unit as VariantUnitResource)"
                      />
                    </div>
                  </template>
                  <span v-else class="text-surface-300 dark:text-surface-600">&mdash;</span>
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-8">
                  <i class="fa fa-box-open text-3xl text-surface-300 dark:text-surface-600 mb-2 block" />
                  <p class="text-surface-500">{{ t("No purchase units defined") }}</p>
                </div>
              </template>
            </DataTable>
          </div>
          <div class="flex justify-end mt-3">
            <Button v-can="'inventory.edit'" :label="t('Add Purchase Unit')" icon="fa fa-plus" @click="openCreate" />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? t('Edit Unit') : t('Add Unit')"
      modal
      :style="{ width: 'min(450px, calc(100vw - 2rem))' }"
    >
      <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
        <div class="flex flex-col gap-2">
          <label for="unit-name">
            {{ t("Name") }}
            <span class="text-red-400">*</span>
          </label>
          <AutoComplete
            id="unit-name"
            v-model="nameField"
            v-bind="nameAttrs"
            :suggestions="filteredUnits"
            option-label="name"
            @complete="searchUnits"
            :delay="0"
            :min-length="1"
            :invalid="submitCount > 0 && !!errors.name"
            :empty-search-message="t('No results found')"
            placeholder="e.g. Caja, Docena, Blister"
            fluid
          >
            <template #option="{ option }">
              <div class="flex items-center gap-2">
                <span>{{ option.name }}</span>
                <span v-if="option.abbreviation" class="text-surface-500 text-sm">({{ option.abbreviation }})</span>
              </div>
            </template>
            <template #empty>
              <div class="flex items-center gap-2 px-3 py-2 text-surface-500">
                <i class="fa fa-keyboard" />
                <span>{{ t("No matches — type a custom name") }}</span>
              </div>
            </template>
          </AutoComplete>
          <small class="text-surface-500">{{ t("Suggestions are optional") }}</small>
          <Message v-if="submitCount > 0 && errors.name" severity="error" size="small" :closable="false">{{ errors.name }}</Message>
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-conversion">
            {{ t("Conversion Factor") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber
            id="unit-conversion"
            v-model="conversionField"
            v-bind="conversionAttrs"
            :min="1"
            :use-grouping="false"
            :invalid="submitCount > 0 && !!errors.conversion_factor"
          />
          <small v-if="conversionField && nameField" class="text-surface-500">
            {{ `1 ${nameField} ${t("is equal to")} ${conversionField} ${baseUnitName}` }}
          </small>
          <Message v-if="submitCount > 0 && errors.conversion_factor" severity="error" size="small" :closable="false">
            {{ errors.conversion_factor }}
          </Message>
        </div>

        <div v-if="formType === 'sale'" class="flex flex-col gap-2">
          <label for="unit-price">
            {{ t("Price") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber
            id="unit-price"
            v-model="priceField"
            v-bind="priceAttrs"
            mode="currency"
            :currency="currency"
            :min="0"
            :invalid="submitCount > 0 && !!errors.price"
          />
          <div v-if="suggestedPrice !== null" class="flex items-center gap-1">
            <small class="text-surface-500">
              {{ t("Suggested price") }}: {{ formatCurrency(String(suggestedPrice)) }}
              <span class="text-surface-400">({{ formatCurrency(String(props.variant.price)) }} × {{ conversionField }})</span>
            </small>
            <Button
              v-can="'inventory.edit'"
              v-tooltip.top="t('Apply')"
              icon="fa fa-circle-info"
              link
              size="small"
              @click="applySuggestedPrice"
            />
          </div>
          <Message v-if="submitCount > 0 && errors.price" severity="error" size="small" :closable="false">{{ errors.price }}</Message>
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-status">{{ t("Status") }}</label>
          <Select
            id="unit-status"
            v-model="statusField"
            v-bind="statusAttrs"
            :options="statusOptions"
            option-label="name"
            option-value="value"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-sort-order">{{ t("Display Order") }}</label>
          <InputNumber id="unit-sort-order" v-model="sortOrderField" v-bind="sortOrderAttrs" :min="0" :use-grouping="false" />
        </div>
      </form>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" @click="dialogVisible = false" />
        <Button :label="isEditing ? t('Save') : t('Add Unit')" @click="onSubmit" />
      </template>
    </Dialog>
  </div>
</template>
<style scoped>
.p-tab-active {
  @apply bg-primary-50 dark:bg-primary-900;
}
</style>