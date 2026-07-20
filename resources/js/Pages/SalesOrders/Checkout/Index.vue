<script setup lang="ts">
import { Button, Card, Divider, useConfirm, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { number, object, string } from "yup";
import { computed, nextTick, ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import AppLayout from "@layouts/admin.vue";
import CustomerSelect from "../Components/CustomerSelect.vue";
import SOFinancialSummary from "../Components/SOFinancialSummary.vue";
import SOPaymentsPanel from "../Components/SOPaymentsPanel.vue";
import OrderItemsTable from "../Components/OrderItemsTable.vue";
import OrderStatusBadge from "../Components/OrderStatusBadge.vue";
import type { SalesOrderPaymentForm, SalesOrderResponse } from "@/Types/sales-order-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{ order: SalesOrderResponse }>();
const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();
const processing = ref(false);
const customerId = ref<number | null>(props.order.customer_id);
const payments = ref<SalesOrderPaymentForm[]>([{ payment_method: "cash", amount: props.order.total, reference: null }]);

const schema = toTypedSchema(
  object({
    discount_value: number().required().min(0),
    notes: string().nullable().max(1000),
  }),
);
const { handleSubmit, errors, defineField, setErrors, setFieldValue, submitCount } = useForm({
  validationSchema: schema,
  initialValues: { discount_value: props.order.discount_value, notes: props.order.notes },
});
const [discountValue, discountValueAttrs] = defineField("discount_value");
const [notes, notesAttrs] = defineField("notes");

const isDraft = computed(() => props.order.status === "draft");
const canPay = computed(() => ["confirmed", "delivered"].includes(props.order.status) && props.order.payment_status === "pending");
const paymentTotal = computed(() => payments.value.reduce((sum, payment) => sum + payment.amount, 0));
const changeDue = computed(() => Math.max(0, paymentTotal.value - props.order.total));

const saveDetails = handleSubmit((formValues) => {
  processing.value = true;
  router.put(
    route("sales-orders.checkout.update", props.order.id),
    { customer_id: customerId.value, discount_type: "flat", discount_value: formValues.discount_value, notes: formValues.notes || null },
    {
      onSuccess: () => toast.add({ severity: "success", summary: t("Success"), detail: t("Checkout details updated successfully"), life: 3000 }),
      onError: (serverErrors) => {
        setErrors(serverErrors);
        nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus());
      },
      onFinish: () => (processing.value = false),
    },
  );
});

function confirmOrder() {
  confirm.require({
    message: t("Confirming deducts inventory and locks products and prices."),
    header: t("Confirm Sales Order"),
    icon: "fa fa-triangle-exclamation",
    accept: () => mutate("patch", "sales-orders.confirm", t("Sales order confirmed successfully")),
  });
}

function payOrder() {
  processing.value = true;
  router.post(route("sales-orders.pay", props.order.id), { payments: payments.value }, {
    onSuccess: () => toast.add({ severity: "success", summary: t("Success"), detail: t("Sales order paid successfully"), life: 3000 }),
    onFinish: () => (processing.value = false),
  });
}

function mutate(method: "patch", routeName: string, message: string) {
  processing.value = true;
  router[method](route(routeName, props.order.id), {}, {
    onSuccess: () => toast.add({ severity: "success", summary: t("Success"), detail: message, life: 3000 }),
    onFinish: () => (processing.value = false),
  });
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="router.visit(route('sales-orders.show', order.id))" />
        <div>
          <h2 class="m-0 text-2xl font-bold">{{ t("Checkout") }} #{{ order.id }}</h2>
          <div class="mt-1 flex gap-2"><OrderStatusBadge :status="order.status" /><span class="text-sm text-surface-500">{{ t(order.payment_status) }}</span></div>
        </div>
      </div>
      <Button v-if="isDraft" :label="t('Edit Products')" icon="fa fa-pen" severity="secondary" outlined @click="router.visit(route('sales-orders.edit', order.id))" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 flex flex-col gap-4 lg:col-span-8">
        <Card>
          <template #title>{{ t("Items") }}</template>
          <template #content><OrderItemsTable :items="order.items ?? []" /></template>
        </Card>

        <Card v-if="isDraft">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <CustomerSelect v-model="customerId" />
              <Button :label="t('Save Details')" icon="fa fa-save" class="self-start uppercase" :loading="processing" @click="saveDetails" />
              <Divider />
              <Button :label="t('Confirm Order')" icon="fa fa-check" raised class="self-start uppercase" :loading="processing" @click="confirmOrder" />
            </div>
          </template>
        </Card>

        <Card v-if="props.order.status === 'confirmed'">
          <template #content><Button :label="t('Mark as Delivered')" icon="fa fa-truck" :loading="processing" @click="mutate('patch', 'sales-orders.deliver', t('Sales order delivered successfully'))" /></template>
        </Card>

        <template v-if="canPay">
          <SOPaymentsPanel v-model="payments" :total-amount="order.total" />
          <Card v-if="changeDue > 0"><template #content><div class="flex justify-between font-medium"><span>{{ t("Change Due") }}</span><span>{{ changeDue.toFixed(2) }}</span></div></template></Card>
          <Button :label="t('Collect Payment')" icon="fa fa-credit-card" raised class="self-start uppercase" :loading="processing" @click="payOrder" />
        </template>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <SOFinancialSummary
          :sub-total="order.sub_total"
          :total="order.total"
          :discount-value="discountValue"
          :discount-attrs="discountValueAttrs"
          :max-discount="order.sub_total"
          :tax-amount="order.tax_amount"
          :notes="notes"
          :notes-attrs="notesAttrs"
          :submit-count="submitCount"
          :errors="errors"
          @update:discount-value="setFieldValue('discount_value', $event ?? 0)"
          @update:notes="setFieldValue('notes', $event)"
        />
      </div>
    </div>
  </div>
</template>
