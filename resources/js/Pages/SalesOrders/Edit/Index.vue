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
import { useAuth } from "@composables/useAuth";
import SOLineItemsTable from "../Components/SOLineItemsTable.vue";
import SOFinancialSummary from "../Components/SOFinancialSummary.vue";
import SOPaymentsPanel from "../Components/SOPaymentsPanel.vue";
import CustomerSelect from "../Components/CustomerSelect.vue";
import type { LineItem } from "../Components/SOLineItemsTable.vue";
import type { SalesOrderResponse, SalesOrderPaymentForm } from "@/Types/sales-order-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  order: SalesOrderResponse;
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
    discount_value: props.order.discount_value ?? 0,
    notes: props.order.notes,
  },
});

const [discountValue, discountValueAttrs] = defineField("discount_value");
const [notes, notesAttrs] = defineField("notes");

const selectedCustomerId = ref<number | null>(props.order.customer_id);

// Pre-populate line items from order
const lineItems = ref<LineItem[]>(
  (props.order.items ?? []).map((item) => ({
    id: crypto.randomUUID(),
    product_variant_id: item.product_variant_id,
    product_name: item.product_variant?.product?.name ?? "—",
    variant_label: item.product_variant?.identifier ?? "—",
    sale_unit_id: item.sale_unit_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
    conversion_factor: item.conversion_factor,
    line_total: item.line_total,
    stock: null,
    minimum_stock_level: null,
    sale_units: [],
    sale_unit: item.sale_unit,
  })),
);

// Pre-populate payments from order
const payments = ref<SalesOrderPaymentForm[]>(
  (props.order.payments ?? []).map((p) => ({
    id: p.id,
    payment_method: p.payment_method,
    amount: p.amount,
    reference: p.reference,
  })),
);

const itemsError = ref("");
const paymentsError = ref("");

// Totals computation — must mirror SalesOrderService::calculateTotals()
const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.line_total, 0));
const discountAmount = computed(() => values.discount_value ?? 0);
const taxRate = computed(() => Number(getSetting("sales", "tax_rate", 0) ?? 0) / 100);
const taxAmount = computed(() => Math.round((subTotal.value - discountAmount.value) * taxRate.value * 100) / 100);
const totalAmount = computed(
  () => Math.round((subTotal.value - discountAmount.value + taxAmount.value) * 100) / 100,
);

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

  router.put(route("sales-orders.update", props.order.id), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Sales order updated successfully"), life: 3000 });
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
  router.visit(route("sales-orders.show", props.order.id));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Edit Sales Order") }} #{{ order.id }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" @click="submit" />
    </div>

    <ConfirmDialog />

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <CustomerSelect
              v-model="selectedCustomerId"
              :initial-customer="order.customer ? {
                id: order.customer.id!,
                first_name: order.customer.first_name ?? '',
                last_name: order.customer.last_name ?? '',
                email: order.customer.email,
                phone: order.customer.phone,
                tax_id: order.customer.tax_id ?? '',
                tax_id_name: order.customer.tax_id_name ?? '',
              } : null"
            />
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