<script setup lang="ts">
import {
  Button,
  Column,
  ConfirmDialog,
  DataTable,
  Dialog,
  InputNumber,
  InputText,
  Select,
  TabPanel,
  TabView,
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

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";

const activeInnerTab = ref(0);
const dialogVisible = ref(false);
const editing = ref<VariantUnitResource | null>(null);
const isEditing = computed(() => !!editing.value);

const saleUnits = computed(() => (props.variant.sale_units ?? []).filter((u) => u.type === "sale"));
const purchaseUnits = computed(() => (props.variant.purchase_units ?? []).filter((u) => u.type === "purchase"));
const currentType = computed(() => (activeInnerTab.value === 0 ? "sale" : "purchase") as "sale" | "purchase");

const form = useForm({
  type: "sale" as "sale" | "purchase",
  name: "",
  conversion_factor: 1,
  price: null as number | null,
  status: "active" as "active" | "inactive",
  sort_order: 0,
});

const statusLabel = (s: string) => (s === "active" ? t("Active") : t("Inactive"));
const conversionLabel = (unit: VariantUnitResource): string => {
  const base = props.product.measurement_unit?.abbreviation ?? "pc";
  return `${unit.conversion_factor} ${base}`;
};

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.type = currentType.value;
  form.price = currentType.value === "sale" ? 0 : null;
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
  dialogVisible.value = true;
};

const onSubmit = () => {
  if (isEditing.value) {
    form.put(route("products.variants.units.update", [props.product.id, props.variant.id, editing.value!.id]), {
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

  form.post(route("products.variants.units.store", [props.product.id, props.variant.id]), {
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
    acceptClass: "p-button-danger",
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

watch(activeInnerTab, () => {
  dialogVisible.value = false;
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <ConfirmDialog />

    <!-- Base unit info -->
    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
      <span>
        {{ t("Base unit") }}: {{ product.measurement_unit?.name ?? t("Unit") }} ({{ product.measurement_unit?.abbreviation ?? "pc" }})
      </span>
      <span>|</span>
      <span>{{ t("Stock") }}: {{ variant.stock }} {{ product.measurement_unit?.abbreviation ?? "pcs" }}</span>
    </div>

    <TabView v-model:active-index="activeInnerTab">
      <TabPanel :header="t('Sale Units')">
        <div class="flex justify-end mb-3">
          <Button v-can="'inventory.edit'" :label="t('Add Sale Unit')" icon="fa fa-plus" outlined size="small" @click="openCreate" />
        </div>
        <DataTable :value="saleUnits" size="small">
          <Column :header="t('Name')">
            <template #body="{ data: unit }">
              {{ unit.name }}
            </template>
          </Column>
          <Column :header="t('Conversion Factor')">
            <template #body="{ data: unit }">
              {{ conversionLabel(unit) }}
            </template>
          </Column>
          <Column :header="t('Price')">
            <template #body="{ data: unit }">
              {{ unit.price !== null ? formatCurrency(String(unit.price)) : "---" }}
            </template>
          </Column>
          <Column :header="t('Status')">
            <template #body="{ data: unit }">
              <Tag :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
            </template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data: unit }">
              <div class="flex gap-1">
                <Button
                  v-can="'inventory.edit'"
                  icon="fa fa-pen"
                  text
                  rounded
                  size="small"
                  v-tooltip.top="t('Edit')"
                  @click="openEdit(unit)"
                />
                <Button
                  v-can="'inventory.edit'"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="small"
                  severity="danger"
                  v-tooltip.top="t('Delete')"
                  @click="onDelete(unit)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8">
              <i class="fa fa-box-open text-3xl text-surface-300 dark:text-surface-600 mb-2 block" />
              <p class="text-gray-500">{{ t("No purchase units defined") }}</p>
            </div>
          </template>
        </DataTable>
        <div class="flex justify-end mb-3">
          <Button v-can="'inventory.edit'" :label="t('Add Purchase Unit')" icon="fa fa-plus" outlined size="small" @click="openCreate" />
        </div>
        <DataTable :value="purchaseUnits" size="small">
          <Column :header="t('Name')">
            <template #body="{ data: unit }">
              {{ unit.name }}
            </template>
          </Column>
          <Column :header="t('Conversion Factor')">
            <template #body="{ data: unit }">
              {{ conversionLabel(unit) }}
            </template>
          </Column>
          <Column :header="t('Status')">
            <template #body="{ data: unit }">
              <Tag :value="statusLabel(unit.status)" :severity="unit.status === 'active' ? 'success' : 'warn'" />
            </template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data: unit }">
              <div class="flex gap-1">
                <Button
                  v-can="'inventory.edit'"
                  icon="fa fa-pen"
                  text
                  rounded
                  size="small"
                  v-tooltip.top="t('Edit')"
                  @click="openEdit(unit)"
                />
                <Button
                  v-can="'inventory.edit'"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="small"
                  severity="danger"
                  v-tooltip.top="t('Delete')"
                  @click="onDelete(unit)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center text-gray-500 py-4">
              {{ t("No purchase units defined") }}
            </div>
          </template>
        </DataTable>
      </TabPanel>
    </TabView>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="dialogVisible" :header="isEditing ? t('Edit Unit') : t('Add Unit')" modal :style="{ width: '450px' }">
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <label for="unit-name">
            {{ t("Name") }}
            <span class="text-red-400">*</span>
          </label>
          <InputText id="unit-name" v-model="form.name" autocomplete="off" />
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-conversion">
            {{ t("Conversion Factor") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber id="unit-conversion" v-model="form.conversion_factor" :min="1" :use-grouping="false" />
        </div>

        <div v-if="form.type === 'sale'" class="flex flex-col gap-2">
          <label for="unit-price">
            {{ t("Price") }}
            <span class="text-red-400">*</span>
          </label>
          <InputNumber id="unit-price" v-model="form.price" mode="currency" :currency="currency" :min="0" />
        </div>

        <div class="flex flex-col gap-2">
          <label for="unit-status">{{ t("Status") }}</label>
          <Select
            id="unit-status"
            v-model="form.status"
            :options="[
              { name: t('Active'), value: 'active' },
              { name: t('Inactive'), value: 'inactive' },
            ]"
            option-label="name"
            option-value="value"
          />
        </div>
      </div>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="isEditing ? t('Save') : t('Add Unit')" :loading="form.processing" @click="onSubmit" />
      </template>
    </Dialog>
  </div>
</template>
