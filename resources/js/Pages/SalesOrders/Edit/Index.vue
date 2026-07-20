<script setup lang="ts">
import { Button, Card, Dialog, Select, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import type { FormDataConvertible } from "@inertiajs/core";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import { useAuth } from "@composables/useAuth";
import { useStockLedger } from "@composables/useStockLedger";
import SOLineItemsTable from "../Components/SOLineItemsTable.vue";
import SOFinancialSummary from "../Components/SOFinancialSummary.vue";
import SOPaymentsPanel from "../Components/SOPaymentsPanel.vue";
import CustomerSelect from "../Components/CustomerSelect.vue";
import CancellationDialog from "../Components/CancellationDialog.vue";
import FulfillmentDialog from "../Components/FulfillmentDialog.vue";
import OrderItemsTable from "../Components/OrderItemsTable.vue";
import OrderPaymentsTable from "../Components/OrderPaymentsTable.vue";
import OrderTotalsCard from "../Components/OrderTotalsCard.vue";
import SalesOrderStatusStepper from "../Components/SalesOrderStatusStepper.vue";
import SalesOrderSummaryCard from "../Components/SalesOrderSummaryCard.vue";
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

// Read-only store reference for this order. Stock on newly-added items is
// scoped to this store via the picker; the store itself is immutable on edit.
const orderStoreId = computed(() => props.order.store_id ?? null);
const orderStoreName = computed(() => props.order.store?.name ?? null);
const orderStoreOptions = computed(() => (orderStoreName.value !== null ? [{ name: orderStoreName.value, value: orderStoreId.value }] : []));

const selectedCustomerId = ref<number | null>(props.order.customer_id);

// Pre-populate line items from order
const lineItems = ref<LineItem[]>(
  (props.order.items ?? []).map((item) => ({
    id: crypto.randomUUID(),
    product_variant_id: item.product_variant_id,
    product_name: item.product_variant?.product?.name ?? "—",
    brand_name: item.product_variant?.product?.brand?.name ?? null,
    // For default-only variants (no identifier), show the sale unit name as
    // the variant label instead of repeating the product name on line 2.
    variant_label: item.product_variant?.identifier
      ? item.product_variant.identifier
      : item.sale_unit?.name ?? t("Unit"),
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
const payments = ref<SalesOrderPaymentForm[]>([{ payment_method: "cash", amount: Math.max(0, props.order.outstanding_balance), reference: null }]);

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

// Live per-variant base-stock ledger. On Edit, pre-populated items carry
// stock=null (no fresh snapshot), so the ledger returns null for those
// variants and the table falls back to a neutral "—" until the operator
// re-searches and adds a fresh row (which carries the snapshot).
const { getRemainingBase, getRemainingBaseExcludingLine, hasOversell } = useStockLedger(lineItems);

// Totals computation — must mirror SalesOrderService::calculateTotals()
const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.line_total, 0));
const discountAmount = computed(() => values.discount_value ?? 0);
const taxRate = computed(() => Number(getSetting("sales", "tax_rate", "0") ?? 0) / 100);
const taxAmount = computed(() => Math.round((subTotal.value - discountAmount.value) * taxRate.value * 100) / 100);
const totalAmount = computed(() => Math.round((subTotal.value - discountAmount.value + taxAmount.value) * 100) / 100);

const isDraft = computed(() => props.order.status === "draft");
const canPay = computed(() => ["validated", "fulfilled"].includes(props.order.status) && props.order.payment_status !== "paid");
const canFulfill = computed(() => props.order.status === "validated");
const canCancel = computed(() => props.order.status === "draft" || (props.order.status === "validated" && props.order.payment_status === "pending"));
const paymentsVisible = ref(false);
const fulfillmentVisible = ref(false);
const cancellationVisible = ref(false);
const actionProcessing = ref(false);

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  paymentsError.value = "";

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
  };

  submitting.value = true;
  router.put(route("sales-orders.update", props.order.id), payload, {
    onSuccess: () => {
      submitting.value = false;
      toast.add({ severity: "success", summary: t("Success"), detail: t("Sales order updated successfully"), life: 3000 });
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

function runAction(method: "patch" | "post", routeName: string, data: Record<string, FormDataConvertible>, message: string): void {
  actionProcessing.value = true;
  router[method](route(routeName, props.order.id), data, {
    onSuccess: () => toast.add({ severity: "success", summary: t("Success"), detail: t(message), life: 3000 }),
    onError: (serverErrors: Record<string, string>) => toast.add({ severity: "error", summary: t("Error"), detail: Object.values(serverErrors)[0] ?? t("Please review the errors in the form"), life: 3000 }),
    onFinish: () => (actionProcessing.value = false),
  });
}

function validateOrder(): void { runAction("patch", "sales-orders.validate", {}, "Sales order validated successfully"); }
function fulfillOrder(): void { fulfillmentVisible.value = false; runAction("patch", "sales-orders.fulfill", {}, "Sales order fulfilled successfully"); }
function payOrder(): void {
  paymentsError.value = "";
  if (payments.value.length === 0 || payments.value.some((payment) => payment.amount <= 0)) { paymentsError.value = t("Add a valid payment"); return; }
  paymentsVisible.value = false;
  runAction("post", "sales-orders.pay", { payments: payments.value }, "Payment recorded successfully");
}
function cancelOrder(reason: string): void { cancellationVisible.value = false; runAction("patch", "sales-orders.cancel", { reason }, "Sales order cancelled successfully"); }
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Edit Sales Order") }} #{{ order.id }}</h2>
      </div>
      <Button v-if="isDraft" icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="submitting" :disabled="submitting" @click="submit" />
    </div>

    <SalesOrderStatusStepper :status="order.status" />

    <div v-if="isDraft" class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="so-store">{{ t("Store") }}</label>
              <Select
                id="so-store"
                :model-value="orderStoreId"
                :options="orderStoreOptions"
                option-label="name"
                option-value="value"
                disabled
              />
            </div>
            <CustomerSelect
              v-model="selectedCustomerId"
              :initial-customer="
                order.customer
                  ? {
                      id: order.customer.id!,
                      first_name: order.customer.first_name ?? '',
                      last_name: order.customer.last_name ?? '',
                      email: order.customer.email,
                      phone: order.customer.phone,
                      tax_id: order.customer.tax_id ?? '',
                      tax_id_name: order.customer.tax_id_name ?? '',
                    }
                  : null
              "
            />
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <SOLineItemsTable
              v-model="lineItems"
              :get-remaining-base="getRemainingBase"
              :get-remaining-base-excluding-line="getRemainingBaseExcludingLine"
              :store-id="orderStoreId"
            />
            <small v-if="itemsError" class="text-red-500 dark:text-red-400 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>

      </div>

      <div class="lg:col-span-4 col-span-12">
        <div class="mb-4"><SalesOrderSummaryCard :order="order" /></div>
        <Card class="mb-4">
          <template #title>{{ t("Status Actions") }}</template>
          <template #content>
            <div class="flex flex-wrap gap-2">
              <Button v-can="'sales.manage'" :label="t('Validate')" icon="fa fa-check" :loading="actionProcessing" @click="validateOrder" />
              <Button v-can="'sales.manage'" :label="t('Cancel')" icon="fa fa-ban" severity="danger" outlined :loading="actionProcessing" @click="cancellationVisible = true" />
            </div>
          </template>
        </Card>
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

    <div v-else class="grid grid-cols-12 gap-4">
      <div class="col-span-12 flex flex-col gap-4 lg:col-span-8">
        <SalesOrderSummaryCard :order="order" />
        <Card>
          <template #title>{{ t("Status Actions") }}</template>
          <template #content>
            <div class="flex flex-wrap gap-2">
              <Button v-if="order.status === 'draft'" v-can="'sales.manage'" :label="t('Validate')" icon="fa fa-check" :loading="actionProcessing" @click="validateOrder" />
              <Button v-if="canFulfill" v-can="'sales.manage'" :label="t('Product Handover')" icon="fa fa-box" :loading="actionProcessing" @click="fulfillmentVisible = true" />
              <Button v-if="canPay" v-can="'sales.manage'" :label="t('Payments')" icon="fa fa-credit-card" :loading="actionProcessing" @click="paymentsVisible = true" />
              <Button v-if="canCancel" v-can="'sales.manage'" :label="t('Cancel')" icon="fa fa-ban" severity="danger" outlined :loading="actionProcessing" @click="cancellationVisible = true" />
            </div>
          </template>
        </Card>
        <Card><template #title>{{ t("Items") }}</template><template #content><OrderItemsTable :items="order.items ?? []" /></template></Card>
        <Card><template #title>{{ t("Payments") }}</template><template #content><OrderPaymentsTable :payments="order.payments ?? []" :total="order.total" /></template></Card>
      </div>
      <div class="col-span-12 lg:col-span-4"><OrderTotalsCard :sub-total="order.sub_total" :discount="order.discount" :tax-amount="order.tax_amount" :total="order.total" :discount-type="order.discount_type" :discount-value="order.discount_value" /></div>
    </div>

    <Dialog v-model:visible="paymentsVisible" modal :header="t('Payments')" class="w-full max-w-3xl">
      <SOPaymentsPanel v-model="payments" :total-amount="order.outstanding_balance" :error="paymentsError" />
      <template #footer><Button :label="t('Close')" severity="secondary" text @click="paymentsVisible = false" /><Button :label="t('Record Payment')" icon="fa fa-credit-card" :loading="actionProcessing" @click="payOrder" /></template>
    </Dialog>
    <FulfillmentDialog v-model:visible="fulfillmentVisible" :order="order" :processing="actionProcessing" @confirm="fulfillOrder" />
    <CancellationDialog v-model:visible="cancellationVisible" :processing="actionProcessing" @confirm="cancelOrder" />
  </div>
</template>
