<script setup lang="ts">
import { Button, Card, useToast, ConfirmDialog } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import SOLineItemsTable from "../Components/SOLineItemsTable.vue";
import SOFinancialSummary from "../Components/SOFinancialSummary.vue";
import SOPaymentsPanel from "../Components/SOPaymentsPanel.vue";
import CustomerSelect from "../Components/CustomerSelect.vue";
import type { LineItem } from "../Components/SOLineItemsTable.vue";
import type { SalesOrderPaymentForm, CustomerOption } from "@/Types/sales-order-types";

defineOptions({ layout: AppLayout });

defineProps<{
  customers: CustomerOption[];
}>();

const toast = useToast();
const { t } = useI18n();

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

const selectedCustomerId = ref<number | null>(null);
const lineItems = ref<LineItem[]>([]);
const payments = ref<SalesOrderPaymentForm[]>([
  { payment_method: "cash", amount: 0, reference: null },
]);
const itemsError = ref("");
const paymentsError = ref("");

// Totals computation
const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.line_total, 0));
const discountAmount = computed(() => values.discount_value ?? 0);
const taxRate = 0; // TODO: read from settings
const taxAmount = computed(() => (subTotal.value - discountAmount.value) * taxRate);
const totalAmount = computed(() => subTotal.value - discountAmount.value + taxAmount.value);

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  paymentsError.value = "";

  if (lineItems.value.length === 0) {
    itemsError.value = t("At least one item is required");
    return;
  }

  const paymentsTotal = payments.value.reduce((sum, p) => sum + p.amount, 0);
  const paymentsDifference = Math.abs(paymentsTotal - totalAmount.value);
  if (paymentsDifference > 0.01) {
    paymentsError.value = t("Payments must equal order total");
    return;
  }

  const payload = {
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

  router.post(route("sales-orders.store"), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Sales order created successfully"), life: 3000 });
    },
    onError: (errs: Record<string, string>) => {
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
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" @click="submit" />
    </div>

    <ConfirmDialog />

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <CustomerSelect v-model="selectedCustomerId" :customers="customers" />
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <SOLineItemsTable v-model="lineItems" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>

        <SOPaymentsPanel
          v-model="payments"
          :total-amount="totalAmount"
          :error="paymentsError"
        />
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