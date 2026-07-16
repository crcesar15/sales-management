<script setup lang="ts">
import { Button, Card, Select, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import { useAuth } from "@composables/useAuth";
import { useStockLedger } from "@composables/useStockLedger";
import { usePaymentsTotals } from "@composables/usePaymentsTotals";
import SOLineItemsTable from "../Components/SOLineItemsTable.vue";
import SOFinancialSummary from "../Components/SOFinancialSummary.vue";
import SOPaymentsPanel from "../Components/SOPaymentsPanel.vue";
import CustomerSelect from "../Components/CustomerSelect.vue";
import type { LineItem } from "../Components/SOLineItemsTable.vue";
import type { StoreOption, SalesOrderPaymentForm } from "@/Types/sales-order-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: StoreOption[];
}>();

const toast = useToast();
const { t } = useI18n();
const { getSetting } = useAuth();

const schema = toTypedSchema(
  object({
    discount_value: number().nullable().optional().min(0, t("Discount must be at least 0")),
    notes: string().nullable().optional().max(1000, t("Notes must not exceed 1000 characters")),
  }),
);

const { handleSubmit, errors, values, defineField, setFieldValue, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    discount_value: 0 as number | null,
    notes: null as string | null,
  },
});

const [discountValue, discountValueAttrs] = defineField("discount_value");
const [notes, notesAttrs] = defineField("notes");

// Store selector — auto-select when the actor has exactly one store.
const selectedStoreId = ref<number | null>(props.stores.length === 1 ? props.stores[0].id : null);
const showStoreSelector = computed(() => props.stores.length > 1);
const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));
const storeError = ref("");

const selectedCustomerId = ref<number | null>(null);
const lineItems = ref<LineItem[]>([]);
const payments = ref<SalesOrderPaymentForm[]>([{ payment_method: "cash", amount: 0, reference: null }]);
const itemsError = ref("");
const paymentsError = ref("");
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

// Totals computation — must mirror SalesOrderService::calculateTotals()
const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.line_total, 0));
const discountAmount = computed(() => values.discount_value ?? 0);
const taxRate = computed(() => Number(getSetting("sales", "tax_rate", "0") ?? 0) / 100);
const taxAmount = computed(() => Math.round((subTotal.value - discountAmount.value) * taxRate.value * 100) / 100);
const totalAmount = computed(() => Math.round((subTotal.value - discountAmount.value + taxAmount.value) * 100) / 100);

// Single-sourced payment totals — shared with SOPaymentsPanel so the submit
// guard and the live mismatch callout cannot drift apart.
const { isBalanced: paymentsAreBalanced } = usePaymentsTotals(payments, totalAmount);

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  paymentsError.value = "";
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

  if (!paymentsAreBalanced.value) {
    failValidation(() => {
      paymentsError.value = t("Payments must equal order total");
    }, "Payments must equal order total");
    return;
  }

  const payload = {
    store_id: selectedStoreId.value,
    customer_id: selectedCustomerId.value,
    discount_type: "flat" as const,
    discount_value: formValues.discount_value ?? 0,
    notes: formValues.notes || null,
    items: lineItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      sale_unit_id: item.sale_unit_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      conversion_factor: item.conversion_factor,
    })),
    payments: payments.value.map((p) => ({
      payment_method: p.payment_method,
      amount: p.amount,
      reference: p.reference || null,
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
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Create Sales Order") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="submitting" :disabled="submitting" @click="submit" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div v-if="showStoreSelector" class="flex flex-col gap-2 mb-3">
              <label for="so-store">{{ t("Store") }}</label>
              <Select
                id="so-store"
                :model-value="selectedStoreId"
                :options="storeOptions"
                option-label="name"
                option-value="value"
                :placeholder="t('Select store')"
                :class="{ 'p-invalid': !!storeError }"
                @update:model-value="onStoreChange"
              />
              <small v-if="storeError" class="text-red-500 dark:text-red-400">{{ storeError }}</small>
            </div>
            <CustomerSelect v-model="selectedCustomerId" />
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <div v-if="selectedStoreId === null" class="flex flex-col items-center justify-center py-10 text-surface-500 dark:text-surface-400">
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

        <SOPaymentsPanel v-if="selectedStoreId !== null" v-model="payments" :total-amount="totalAmount" :error="paymentsError" />
      </div>

      <div class="lg:col-span-4 col-span-12">
        <SOFinancialSummary
          :sub-total="subTotal"
          :total="totalAmount"
          :discount-value="discountValue"
          :discount-attrs="discountValueAttrs"
          :max-discount="subTotal"
          :tax-amount="taxAmount"
          :notes="notes"
          :notes-attrs="notesAttrs"
          :submit-count="submitCount"
          :errors="errors"
          @update:discount-value="setFieldValue('discount_value', $event)"
          @update:notes="setFieldValue('notes', $event)"
        />
      </div>
    </div>
  </div>
</template>
