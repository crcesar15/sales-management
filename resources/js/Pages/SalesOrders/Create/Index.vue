<script setup lang="ts">
import { Button, Card, Select, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import { useStockLedger } from "@composables/useStockLedger";
import SOLineItemsTable from "../Components/SOLineItemsTable.vue";
import OrderTotalsCard from "../Components/OrderTotalsCard.vue";
import SalesOrderStatusStepper from "../Components/SalesOrderStatusStepper.vue";
import type { LineItem } from "../Components/SOLineItemsTable.vue";
import type { StoreOption } from "@/Types/sales-order-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: StoreOption[];
}>();

const toast = useToast();
const { t } = useI18n();
const schema = toTypedSchema(object({}));
const { handleSubmit, setErrors } = useForm({ validationSchema: schema });

// Store selector — auto-select when the actor has exactly one store.
const selectedStoreId = ref<number | null>(props.stores.length === 1 ? props.stores[0].id : null);
const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));
const storeError = ref("");

const lineItems = ref<LineItem[]>([]);
const itemsError = ref("");
const submitting = ref(false);

// Surface a client-side validation failure with the same feedback layer as
// server errors: a warn toast + scroll/focus to the first invalid field.
// The caller still sets the relevant error ref so the inline caption renders.
function failValidation(setter: () => void, reasonKey: string): false {
  setter();
  toast.add({ severity: "warn", summary: t("Cannot save"), detail: t(reasonKey), life: 3000 });
  nextTick(() => {
    document.querySelector<HTMLElement>(".text-red-500, .p-invalid")?.scrollIntoView({ block: "center" });
    document.querySelector<HTMLInputElement>(".text-red-500, .p-invalid")?.focus();
  });
  return false;
}

// Clear line items if the store changes mid-order — stock snapshots are
// store-scoped and would otherwise be stale.
function onStoreChange(value: number | null) {
  selectedStoreId.value = value;
  lineItems.value = [];
  itemsError.value = "";
}

// Live per-variant base-stock ledger: shared between picker and table so the
// Available Tag reflects in-progress allocation, and oversell is caught pre-submit.
const { getRemainingBase, getRemainingBaseExcludingLine, hasOversell } = useStockLedger(lineItems);

const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.line_total, 0));
const totalAmount = subTotal;

const submit = handleSubmit(() => {
  itemsError.value = "";
  storeError.value = "";

  if (selectedStoreId.value === null) {
    failValidation(() => {
      storeError.value = t("Select a store first");
    }, "Select a store first");
    return;
  }

  if (lineItems.value.length === 0) {
    failValidation(() => {
      itemsError.value = t("At least one item is required");
    }, "At least one item is required");
    return;
  }

  if (hasOversell.value) {
    failValidation(() => {
      itemsError.value = t("One or more items exceeds available stock");
    }, "One or more items exceeds available stock");
    return;
  }

  const payload = {
    store_id: selectedStoreId.value,
    items: lineItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      sale_unit_id: item.sale_unit_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
    })),
  };

  submitting.value = true;
  router.post(route("sales-orders.store"), payload, {
    onSuccess: () => {
      submitting.value = false;
      toast.add({ severity: "success", summary: t("Success"), detail: t("Sales order created successfully"), life: 3000 });
    },
    onError: (errs: Record<string, string>) => {
      submitting.value = false;
      setErrors(errs);
      toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
      nextTick(() => {
        document.querySelector<HTMLInputElement>(".p-invalid")?.focus();
      });
    },
  });
});

function goBack() {
  router.visit(route("sales-orders"));
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button :aria-label="t('Back')" icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Create Sales Order") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="submitting" :disabled="submitting" @click="submit" />
    </div>
    <SalesOrderStatusStepper status="draft" />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 flex flex-col gap-4 lg:col-span-8">
        <section class="flex flex-col gap-2">
          <label for="so-store" class="font-medium text-xl">{{ t("Store") }}</label>
          <Select
            id="so-store"
            :model-value="selectedStoreId"
            :options="storeOptions"
            option-label="name"
            option-value="value"
            :placeholder="t('Select store')"
            :class="{ 'p-invalid': !!storeError}"
            size="large"
            :disabled="stores.length === 1"
            @update:model-value="onStoreChange"
          />
          <small v-if="storeError" class="text-red-500 dark:text-red-400">{{ storeError }}</small>
          <small v-else-if="stores.length === 0" class="text-surface-500 dark:text-surface-400">
            {{ t("No active stores are assigned to your account") }}
          </small>
        </section>

        <Card class="!border !border-surface-200 !shadow-none dark:!border-surface-700">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <div
              v-if="selectedStoreId === null"
              class="flex flex-col items-center justify-center py-10 text-surface-500 dark:text-surface-400"
            >
              <i class="fa fa-store text-4xl mb-3"></i>
              <span class="font-medium text-lg mb-1">{{ t("Select a store to add products") }}</span>
              <small>{{ t("Stock availability depends on the selected store") }}</small>
            </div>
            <template v-else>
              <SOLineItemsTable
                v-model="lineItems"
                :get-remaining-base="getRemainingBase"
                :get-remaining-base-excluding-line="getRemainingBaseExcludingLine"
                :store-id="selectedStoreId"
              />
              <small v-if="itemsError" class="text-red-500 dark:text-red-400 mt-2 block">{{ itemsError }}</small>
            </template>
          </template>
        </Card>
      </div>

      <aside class="col-span-12 lg:col-span-4">
        <div class="lg:sticky lg:top-4">
          <OrderTotalsCard :sub-total="subTotal" :total="totalAmount" :discount="0" :tax-amount="0" />
        </div>
      </aside>
    </div>
  </div>
</template>
