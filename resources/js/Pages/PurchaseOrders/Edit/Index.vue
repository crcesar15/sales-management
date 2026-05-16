<script setup lang="ts">
import {
  Button,
  Card,
  InputNumber,
  Textarea,
  DatePicker,
  Select,
  Popover,
  useToast,
  ConfirmDialog,
} from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string, date } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import POLineItemsTable from "../Components/POLineItemsTable.vue";
import POTotalsPanel from "../Components/POTotalsPanel.vue";
import type { PurchaseOrderResponse } from "@/Types/purchase-order-types";
import type { LineItem } from "../Components/POLineItemsTable.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrder: PurchaseOrderResponse;
  vendors: Array<{ id: number; fullname: string; email: string | null; phone: string | null; address: string | null }>;
}>();

const toast = useToast();
const { t } = useI18n();

const vendorOptions = computed(() => props.vendors.map((v) => ({ name: v.fullname, value: v.id })));
const vendorInfoPopover = ref();

const schema = toTypedSchema(
  object({
    vendor_id: number().required().typeError(t("Vendor is required")),
    order_date: date().required(t("Order date is required")),
    expected_arrival_date: date().nullable().optional(),
    discount: number().nullable().optional().min(0, t("Discount must be at least 0")),
    notes: string().nullable().optional().max(1000, t("Notes must not exceed 1000 characters")),
  }),
);

const { handleSubmit, errors, values, defineField, setFieldValue, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    vendor_id: props.purchaseOrder.vendor_id,
    order_date: props.purchaseOrder.order_date ? new Date(props.purchaseOrder.order_date) : undefined,
    expected_arrival_date: props.purchaseOrder.expected_arrival_date ? new Date(props.purchaseOrder.expected_arrival_date) : null,
    discount: props.purchaseOrder.discount ?? 0,
    notes: props.purchaseOrder.notes,
  },
});

const [orderDate, orderDateAttrs] = defineField("order_date");
const [expectedArrivalDate, expectedArrivalDateAttrs] = defineField("expected_arrival_date");
const [discount, discountAttrs] = defineField("discount");
const [notes, notesAttrs] = defineField("notes");

const lineItems = ref<LineItem[]>(
  props.purchaseOrder.line_items.map((item) => ({
    id: String(item.id),
    catalog_id: 0,
    product_variant_id: item.product_variant_id,
    product_name: item.product_variant?.product?.name ?? "—",
    variant_label: item.product_variant?.name ?? item.product_variant?.identifier ?? "—",
    quantity: item.quantity,
    price: item.price,
    total: item.total,
  })),
);
const itemsError = ref("");

const selectedVendor = computed(() => props.vendors.find((v) => v.id === values.vendor_id) ?? null);

const subTotal = computed(() => lineItems.value.reduce((sum, item) => sum + item.total, 0));
const total = computed(() => subTotal.value - (values.discount ?? 0));

function toggleVendorInfo(event: Event) {
  vendorInfoPopover.value.toggle(event);
}

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  if (lineItems.value.length === 0) {
    itemsError.value = t("At least one item is required");
    return;
  }

  const payload = {
    vendor_id: formValues.vendor_id,
    order_date: formValues.order_date ? formValues.order_date.toISOString().split("T")[0] : "",
    expected_arrival_date: formValues.expected_arrival_date ? formValues.expected_arrival_date.toISOString().split("T")[0] : null,
    discount: formValues.discount ?? 0,
    notes: formValues.notes || null,
    items: lineItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      quantity: item.quantity,
    })),
  };

  router.put(route("purchase-orders.update", props.purchaseOrder.id), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order updated successfully"), life: 3000 });
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
  router.visit(route("purchase-orders.show", props.purchaseOrder.id));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Edit Purchase Order") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" @click="submit" />
    </div>

    <ConfirmDialog />

    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Vendor") }}</template>
          <template #content>
            <div class="flex flex-col gap-2">
              <div class="flex items-end gap-2">
                <div class="flex-1">
                  <label for="vendor">
                    {{ t("Vendor") }}
                    <span class="text-red-500">*</span>
                  </label>
                  <Select
                    id="vendor"
                    :model-value="values.vendor_id"
                    :options="vendorOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select a Vendor')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.vendor_id }"
                    filter
                    @update:model-value="setFieldValue('vendor_id', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.vendor_id" class="text-red-400 dark:text-red-300">{{ errors.vendor_id }}</small>
                </div>
                <Button
                  v-if="selectedVendor?.id"
                  v-tooltip.top="t('Vendor Information')"
                  icon="fa fa-eye"
                  text
                  size="small"
                  @click="toggleVendorInfo"
                />
              </div>
              <Popover ref="vendorInfoPopover">
                <div v-if="selectedVendor" class="p-4">
                  <h4 class="text-lg font-bold mb-2">{{ t("Vendor Information") }}</h4>
                  <p><strong>{{ t("Fullname") }}:</strong> {{ selectedVendor.fullname }}</p>
                  <p v-if="selectedVendor.email"><strong>{{ t("Email") }}:</strong> {{ selectedVendor.email }}</p>
                  <p v-if="selectedVendor.phone"><strong>{{ t("Phone") }}:</strong> {{ selectedVendor.phone }}</p>
                  <p v-if="selectedVendor.address"><strong>{{ t("Address") }}:</strong> {{ selectedVendor.address }}</p>
                </div>
              </Popover>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Line Items") }}</template>
          <template #content>
            <POLineItemsTable v-model="lineItems" :vendor-id="selectedVendor?.id ?? null" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>

        <div v-if="lineItems.length > 0">
          <POTotalsPanel :sub-total="subTotal" :discount="values.discount ?? 0" :total="total" />
        </div>
      </div>

      <div class="md:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-1">
                <label for="order-date">
                  {{ t("Order Date") }}
                  <span class="text-red-500">*</span>
                </label>
                <DatePicker
                  id="order-date"
                  v-model="orderDate"
                  v-bind="orderDateAttrs"
                  show-icon
                  :class="{ 'p-invalid': submitCount > 0 && !!errors.order_date }"
                />
                <small v-if="submitCount > 0 && errors.order_date" class="text-red-400 dark:text-red-300">{{ errors.order_date }}</small>
              </div>

              <div class="flex flex-col gap-1">
                <label for="expected-arrival-date">{{ t("Expected Arrival Date") }}</label>
                <DatePicker id="expected-arrival-date" v-model="expectedArrivalDate" v-bind="expectedArrivalDateAttrs" show-icon />
              </div>

              <div class="flex flex-col gap-1">
                <label for="discount">{{ t("Discount") }}</label>
                <InputNumber
                  id="discount"
                  v-model="discount"
                  v-bind="discountAttrs"
                  mode="currency"
                  currency="BOB"
                  :min="0"
                  :max="subTotal"
                />
              </div>

              <div class="flex flex-col gap-1">
                <label for="notes">{{ t("Notes") }}</label>
                <Textarea id="notes" v-model="notes" v-bind="notesAttrs" rows="4" :class="{ 'p-invalid': submitCount > 0 && !!errors.notes }" />
                <small v-if="submitCount > 0 && errors.notes" class="text-red-400 dark:text-red-300">{{ errors.notes }}</small>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>