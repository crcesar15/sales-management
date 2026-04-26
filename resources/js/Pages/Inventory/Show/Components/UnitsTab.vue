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
import { ref, computed, watch } from "vue";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, InventoryProductDetail, VariantUnitResource } from "@/Types/inventory-variant-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useAuth } from "@/Composables/useAuth";
import { useMeasurementUnitClient } from "@/Composables/useMeasurementUnitClient";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();
const { fetchMeasurementUnitsApi } = useMeasurementUnitClient();
const currency = getSetting("finance", "currency") ?? "USD";

const activeTab = ref("sale");
const dialogVisible = ref(false);
const editing = ref<VariantUnitResource | null>(null);
const isEditing = computed(() => !!editing.value);

const saleUnits = computed(() => props.variant.sale_units ?? []);
const purchaseUnits = computed(() => props.variant.purchase_units ?? []);
const currentType = computed(() => activeTab.value as "sale" | "purchase");

const baseUnitName = computed(() => props.product.measurement_unit?.name ?? t("Unit"));
const baseUnitAbbr = computed(() => props.product.measurement_unit?.abbreviation ?? "pc");

const unitSuggestions = ref<string[]>([]);

const searchUnits = async (event: { query: string }) => {
  if (!event.query.trim()) {
    unitSuggestions.value = [];
    return;
  }
  try {
    const { data } = await fetchMeasurementUnitsApi<{ data: { name: string }[] }>(`filter=${event.query}`);
    unitSuggestions.value = data.data.map((u) => u.name);
  } catch {
    unitSuggestions.value = [];
  }
};

interface BaseUnitRow {
  isBase: true;
  name: string;
  conversion_factor: number;
  price: string;
  status: string;
}

const saleUnitsWithBase = computed(() => {
  const base: BaseUnitRow = {
    isBase: true,
    name: `${baseUnitName.value} (${t("Base").toLowerCase()})`,
    conversion_factor: 1,
    price: String(props.variant.price),
    status: "base",
  };
  return [base, ...saleUnits.value];
});

const purchaseUnitsWithBase = computed(() => {
  const base: BaseUnitRow = {
    isBase: true,
    name: `${baseUnitName.value} (${t("Base").toLowerCase()})`,
    conversion_factor: 1,
    price: String(props.variant.price),
    status: "base",
  };
  return [base, ...purchaseUnits.value];
});

const form = useForm({
  type: "sale" as "sale" | "purchase",
  name: "",
  conversion_factor: 1,
  price: null as number | null,
  status: "active" as "active" | "inactive",
  sort_order: 0,
});

const statusOptions = computed(() => [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
]);

const statusLabel = (s: string) => (s === "active" ? t("Active") : t("Inactive"));
const conversionLabel = (factor: number): string => `${factor} ${baseUnitAbbr.value}`;

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.type = currentType.value;
  form.price = currentType.value === "sale" ? 0 : null;
  unitSuggestions.value = [];
  dialogVisible.value = true;
};

const openEdit = (unit: VariantUnitResource) => {
  editing.value = unit;
  form.type = unit.type;
  form.name = unit.name;
  form.conversion_factor = unit.conversion_factor;
  form.price = unit.price;
  form.status = unit.status;
  form.sort_order = unit.sort_order;
  unitSuggestions.value = [];
  dialogVisible.value = true;
};

const onSubmit = () => {
  if (isEditing.value) {
    form.put(route("variant.units.update", [props.product.id, props.variant.id, editing.value!.id]), {
      onSuccess: () => {
        dialogVisible.value = false;
        toast.add({ severity: "success", summary: t("Success"), detail: t("Unit updated"), life: 3000 });
        router.reload({ only: ["variant"] });
      },
      onError: (errs) => {
        toast.add({ severity: "error", summary: t("Error"), detail: t(Object.values(errs)[0] ?? "An error occurred"), life: 3000 });
      },
    });
    return;
  }

  form.post(route("variant.units.store", [props.product.id, props.variant.id]), {
    onSuccess: () => {
      dialogVisible.value = false;
      toast.add({ severity: "success", summary: t("Success"), detail: t("Unit created"), life: 3000 });
      router.reload({ only: ["variant"] });
    },
    onError: (errs) => {
      toast.add({ severity: "error", summary: t("Error"), detail: t(Object.values(errs)[0] ?? "An error occurred"), life: 3000 });
    },
  });
};

const onDelete = (unit: VariantUnitResource) => {
  confirm.require({
    message: t("Are you sure you want to delete this unit?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    acceptLabel: t("Delete"),
    rejectLabel: t("Cancel"),
    rejectClass: "p-button-secondary",
    accept: () => {
      router.delete(route("products.variants.units.destroy", [props.product.id, props.variant.id, unit.id]), {
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
</script>

<template>
  <div class="flex flex-col gap-4">
    <ConfirmDialog />

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab
          value="sale"
          :pt="{
            root: ({ context }: any) => ({
              class: [{ '!bg-primary-50 !rounded-t-lg': context.active }],
            }),
          }"
        >
          {{ t("Sale Units") }}
        </Tab>
        <Tab
          value="purchase"
          :pt="{
            root: ({ context }: any) => ({
              class: [{ '!bg-primary-50 !rounded-t-lg': context.active }],
            }),
          }"
        >
          {{ t("Purchase Units") }}
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
          <DataTable :value="saleUnitsWithBase">
            <Column :header="t('Name')">
              <template #body="{ data: unit }">
                <span :class="{ 'italic text-gray-500 dark:text-gray-400': unit.isBase }">
                  {{ unit.name }}
                </span>
              </template>
            </Column>
            <Column :header="t('Conversion Factor')">
              <template #body="{ data: unit }">
                {{ conversionLabel(unit.conversion_factor) }}
              </template>
            </Column>
            <Column :header="t('Price')">
              <template #body="{ data: unit }">
                {{ unit.price !== null ? formatCurrency(String(unit.price)) : "---" }}
              </template>
            </Column>
            <Column :header="t('Status')">
              <template #body="{ data: unit }">
                <Tag v-if="unit.isBase" :value="t('Base')" severity="info" />
                <Tag v-else :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
              </template>
            </Column>
            <Column :header="t('Actions')">
              <template #body="{ data: unit }">
                <template v-if="!unit.isBase">
                  <div class="flex gap-1">
                    <Button
                      v-can="'inventory.edit'"
                      icon="fa fa-pen"
                      text
                      rounded
                      size="small"
                      v-tooltip.top="t('Edit')"
                      @click="openEdit(unit as VariantUnitResource)"
                    />
                    <Button
                      v-can="'inventory.edit'"
                      icon="fa fa-trash"
                      text
                      rounded
                      size="small"
                      v-tooltip.top="t('Delete')"
                      @click="onDelete(unit as VariantUnitResource)"
                    />
                  </div>
                </template>
                <span v-else class="text-gray-300 dark:text-gray-600">&mdash;</span>
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-8">
                <i class="fa fa-box-open text-3xl text-surface-300 dark:text-surface-600 mb-2 block" />
                <p class="text-gray-500">{{ t("No sale units defined") }}</p>
              </div>
            </template>
          </DataTable>
          <div class="flex justify-end mt-3">
            <Button v-can="'inventory.edit'" :label="t('Add Sale Unit')" icon="fa fa-plus" @click="openCreate" />
          </div>
        </TabPanel>

        <!-- Purchase Units Tab -->
        <TabPanel value="purchase">
          <DataTable :value="purchaseUnitsWithBase">
            <Column :header="t('Name')">
              <template #body="{ data: unit }">
                <span :class="{ 'italic text-gray-500 dark:text-gray-400': unit.isBase }">
                  {{ unit.name }}
                </span>
              </template>
            </Column>
            <Column :header="t('Conversion Factor')">
              <template #body="{ data: unit }">
                {{ conversionLabel(unit.conversion_factor) }}
              </template>
            </Column>
            <Column :header="t('Status')">
              <template #body="{ data: unit }">
                <Tag v-if="unit.isBase" :value="t('Base')" severity="info" />
                <Tag v-else :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
              </template>
            </Column>
            <Column :header="t('Actions')">
              <template #body="{ data: unit }">
                <template v-if="!unit.isBase">
                  <div class="flex gap-1">
                    <Button
                      v-can="'inventory.edit'"
                      icon="fa fa-pen"
                      text
                      rounded
                      size="small"
                      v-tooltip.top="t('Edit')"
                      @click="openEdit(unit as VariantUnitResource)"
                    />
                    <Button
                      v-can="'inventory.edit'"
                      icon="fa fa-trash"
                      text
                      rounded
                      size="small"
                      v-tooltip.top="t('Delete')"
                      @click="onDelete(unit as VariantUnitResource)"
                    />
                  </div>
                </template>
                <span v-else class="text-gray-300 dark:text-gray-600">&mdash;</span>
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-8">
                <i class="fa fa-box-open text-3xl text-surface-300 dark:text-surface-600 mb-2 block" />
                <p class="text-gray-500">{{ t("No purchase units defined") }}</p>
              </div>
            </template>
          </DataTable>
          <div class="flex justify-end mt-3">
            <Button v-can="'inventory.edit'" :label="t('Add Purchase Unit')" icon="fa fa-plus" @click="openCreate" />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="dialogVisible" :header="isEditing ? t('Edit Unit') : t('Add Unit')" modal :style="{ width: '450px' }">
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <label for="unit-name">
            {{ t("Name") }}
            <span class="text-red-400">*</span>
          </label>
          <AutoComplete
            id="unit-name"
            v-model="form.name"
            :suggestions="unitSuggestions"
            :invalid="!!form.errors.name"
            :delay="300"
            :min-length="1"
            placeholder="e.g. Caja, Docena, Blister"
            fluid
            @complete="searchUnits"
          />
          <Message v-if="form.errors.name" severity="error" size="small" :closable="false">{{ form.errors.name }}</Message>
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-conversion">
            {{ t("Conversion Factor") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber
            id="unit-conversion"
            v-model="form.conversion_factor"
            :min="1"
            :use-grouping="false"
            :invalid="!!form.errors.conversion_factor"
          />
          <small v-show="form.conversion_factor && form.name">
            {{ `1 ${form.name} ${t("is equal to")} ${form.conversion_factor} ${product.measurement_unit?.name}` }}
          </small>
          <Message v-if="form.errors.conversion_factor" severity="error" size="small" :closable="false">
            {{ form.errors.conversion_factor }}
          </Message>
        </div>

        <div v-if="form.type === 'sale'" class="flex flex-col gap-2">
          <label for="unit-price">
            {{ t("Price") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber id="unit-price" v-model="form.price" mode="currency" :currency="currency" :min="0" :invalid="!!form.errors.price" />
          <Message v-if="form.errors.price" severity="error" size="small" :closable="false">{{ form.errors.price }}</Message>
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-status">{{ t("Status") }}</label>
          <Select id="unit-status" v-model="form.status" :options="statusOptions" option-label="name" option-value="value" />
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-sort-order">{{ t("Display Order") }}</label>
          <InputNumber id="unit-sort-order" v-model="form.sort_order" :min="0" :use-grouping="false" />
        </div>
      </div>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="isEditing ? t('Save') : t('Add Unit')" :loading="form.processing" @click="onSubmit" />
      </template>
    </Dialog>
  </div>
</template>
