<script setup lang="ts">
import { Button, Card, DatePicker, Select, Popover, Textarea, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string, date } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick, watch } from "vue";
import AppLayout from "@layouts/admin.vue";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useAuth } from "@/Composables/useAuth";
import ReceptionLineItemsTable from "../Components/ReceptionLineItems.vue";
import type { ReceptionLineItem } from "../Components/ReceptionLineItems.vue";
import type { PurchaseOrderResponse } from "@/Types/purchase-order-types";
import POStatusBadge from "../../PurchaseOrders/Components/POStatusBadge.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrders: Array<PurchaseOrderResponse>;
  stores: Array<{ id: number; name: string }>;
}>();

const toast = useToast();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));

const schema = toTypedSchema(
  object({
    purchase_order_id: number().required().typeError(t("Purchase order is required")),
    store_id: number().required().typeError(t("Store is required")),
    reception_date: date().nullable().optional(),
    notes: string().nullable().optional().max(1000, t("Notes must not exceed 1000 characters")),
  }),
);

const { handleSubmit, errors, values, defineField, setFieldValue, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    purchase_order_id: undefined as unknown as number,
    store_id: undefined as unknown as number,
    reception_date: null as Date | null,
    notes: null as string | null,
  },
});

const [receptionDate, receptionDateAttrs] = defineField("reception_date");
const [notes, notesAttrs] = defineField("notes");

const lineItems = ref<ReceptionLineItem[]>([]);
const itemsError = ref("");

const selectedPurchaseOrder = computed(() => props.purchaseOrders.find((po) => po.id === values.purchase_order_id) ?? null);

const vendorInfoPopover = ref();

function toggleVendorInfo(event: Event) {
  vendorInfoPopover.value.toggle(event);
}

watch(
  () => values.purchase_order_id,
  () => {
    const po = selectedPurchaseOrder.value;
    if (po) {
      lineItems.value = po.line_items.map((item) => ({
        id: crypto.randomUUID(),
        product_variant_id: item.product_variant_id,
        product_name: item.product_variant?.product?.name ?? "—",
        variant_label: item.product_variant?.name ?? item.product_variant?.identifier ?? "—",
        quantity: item.quantity,
        expiry_date: null,
        purchase_unit: item.catalog_entry?.unit ?? null,
        base_unit: item.product_variant?.product?.measurement_unit
          ? {
              id: item.product_variant.product.measurement_unit.id,
              name: item.product_variant.product.measurement_unit.name,
              abbreviation: item.product_variant.product.measurement_unit.abbreviation,
            }
          : null,
        stock: item.product_variant?.stock ?? null,
        minimum_stock_level: item.product_variant?.minimum_stock_level ?? null,
      }));
    } else {
      lineItems.value = [];
    }
    itemsError.value = "";
  },
);

function formatPoDate(date: string | null): string {
  if (!date) return "—";
  return useDatetimeFormatter(date, getSetting("general", "date_format") ?? "YYYY-MM-DD");
}

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  if (lineItems.value.length === 0) {
    itemsError.value = t("At least one item is required");
    return;
  }

  const payload = {
    purchase_order_id: formValues.purchase_order_id,
    store_id: formValues.store_id,
    reception_date: formValues.reception_date ? formValues.reception_date.toISOString().split("T")[0] : null,
    notes: formValues.notes || null,
    items: lineItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      quantity: item.quantity,
      expiry_date: item.expiry_date ? item.expiry_date.toISOString().split("T")[0] : null,
    })),
  };

  router.post(route("reception-orders.store"), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Reception order created successfully"), life: 3000 });
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
  router.visit(route("reception-orders"));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Create Reception Order") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="false" @click="submit" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-1">
                <label for="purchase-order">
                  {{ t("Purchase Order") }}
                  <span class="text-red-500">*</span>
                </label>
                <Select
                  id="purchase-order"
                  :model-value="values.purchase_order_id"
                  :options="purchaseOrders"
                  option-label="id"
                  option-value="id"
                  :placeholder="t('Select Purchase Order')"
                  :class="{ 'p-invalid': submitCount > 0 && !!errors.purchase_order_id }"
                  filter
                  class="w-full"
                  @update:model-value="setFieldValue('purchase_order_id', $event)"
                >
                  <template #value="slotProps">
                    <span v-if="slotProps.value" class="flex items-center gap-2">
                      <span class="font-medium">#{{ selectedPurchaseOrder?.id }}</span>
                      <span class="text-surface-500">—</span>
                      <span>{{ selectedPurchaseOrder?.vendor?.fullname }}</span>
                      <POStatusBadge :status="selectedPurchaseOrder?.status ?? 'draft'" />
                    </span>
                    <span v-else>{{ slotProps.placeholder }}</span>
                  </template>
                  <template #option="slotProps">
                    <div class="flex items-center justify-between gap-3 w-full py-1">
                      <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                          <span class="font-medium">#{{ slotProps.option.id }}</span>
                          <span class="text-surface-500 truncate">{{ slotProps.option.vendor?.fullname }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-surface-500">
                          <span v-if="slotProps.option.order_date">
                            <i class="fa fa-calendar mr-1" />{{ formatPoDate(slotProps.option.order_date) }}
                          </span>
                          <span>{{ slotProps.option.line_items?.length ?? 0 }} {{ t("items") }}</span>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <span v-if="slotProps.option.total !== null" class="font-medium text-sm tabular-nums">
                          {{ formatCurrency(String(slotProps.option.total ?? 0)) }}
                        </span>
                        <POStatusBadge :status="slotProps.option.status" />
                      </div>
                    </div>
                  </template>
                </Select>
                <small v-if="submitCount > 0 && errors.purchase_order_id" class="text-red-400 dark:text-red-300">{{ errors.purchase_order_id }}</small>
              </div>

              <div v-if="selectedPurchaseOrder" class="flex items-center gap-2">
                <span class="text-sm text-surface-500">{{ t("Vendor") }}:</span>
                <span class="font-medium">{{ selectedPurchaseOrder.vendor?.fullname ?? "—" }}</span>
                <Button
                  v-if="selectedPurchaseOrder.vendor?.id"
                  v-tooltip.top="t('Vendor Information')"
                  icon="fa fa-eye"
                  text
                  size="small"
                  @click="toggleVendorInfo"
                />
                <Popover ref="vendorInfoPopover">
                  <div v-if="selectedPurchaseOrder.vendor" class="p-4 w-72">
                    <h4 class="text-lg font-semibold mb-3">{{ selectedPurchaseOrder.vendor.fullname }}</h4>
                    <div class="flex flex-col gap-2 text-sm">
                      <div v-if="selectedPurchaseOrder.vendor.email" class="flex items-center gap-2">
                        <i class="fa fa-envelope text-surface-400 w-4 text-center" />
                        <a :href="'mailto:' + selectedPurchaseOrder.vendor.email" class="text-primary-500 hover:underline">
                          {{ selectedPurchaseOrder.vendor.email }}
                        </a>
                      </div>
                      <div v-if="selectedPurchaseOrder.vendor.phone" class="flex items-center gap-2">
                        <i class="fa fa-phone text-surface-400 w-4 text-center" />
                        <a :href="'tel:' + selectedPurchaseOrder.vendor.phone" class="text-primary-500 hover:underline">
                          {{ selectedPurchaseOrder.vendor.phone }}
                        </a>
                      </div>
                      <div v-if="selectedPurchaseOrder.vendor.address" class="flex items-start gap-2">
                        <i class="fa fa-location-dot text-surface-400 w-4 text-center mt-0.5" />
                        <span>{{ selectedPurchaseOrder.vendor.address }}</span>
                      </div>
                    </div>
                  </div>
                </Popover>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                  <label for="store">
                    {{ t("Destination Store") }}
                    <span class="text-red-500">*</span>
                  </label>
                  <Select
                    id="store"
                    :model-value="values.store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select Store')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.store_id }"
                    filter
                    class="w-full"
                    @update:model-value="setFieldValue('store_id', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.store_id" class="text-red-400 dark:text-red-300">{{ errors.store_id }}</small>
                </div>
                <div class="flex flex-col gap-1">
                  <label for="reception-date">{{ t("Reception Date") }}</label>
                  <DatePicker
                    id="reception-date"
                    v-model="receptionDate"
                    v-bind="receptionDateAttrs"
                    show-icon
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.reception_date }"
                  />
                  <small v-if="submitCount > 0 && errors.reception_date" class="text-red-400 dark:text-red-300">{{ errors.reception_date }}</small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <div v-if="!selectedPurchaseOrder" class="flex flex-col items-center justify-center py-10 text-surface-400">
              <i class="fa fa-hand-pointer text-4xl mb-3"></i>
              <span class="font-medium text-lg mb-1">{{ t("Select a purchase order first") }}</span>
              <small>{{ t("Line items from purchase order") }}</small>
            </div>
            <ReceptionLineItemsTable v-else v-model="lineItems" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div v-if="selectedPurchaseOrder" class="flex flex-col gap-2">
                <div class="flex justify-between text-sm">
                  <span class="text-surface-500">{{ t("Purchase Order") }}</span>
                  <span class="font-medium">#{{ selectedPurchaseOrder.id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-surface-500">{{ t("Vendor") }}</span>
                  <span class="font-medium">{{ selectedPurchaseOrder.vendor?.fullname ?? "—" }}</span>
                </div>
                <div v-if="selectedPurchaseOrder.total !== null" class="flex justify-between text-sm">
                  <span class="text-surface-500">{{ t("PO Total") }}</span>
                  <span class="font-medium">{{ formatCurrency(String(selectedPurchaseOrder.total ?? 0)) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-surface-500">{{ t("Total Items") }}</span>
                  <span class="font-medium">{{ lineItems.length }}</span>
                </div>
              </div>
              <div v-else class="text-sm text-surface-400 text-center py-4">
                {{ t("Select a purchase order to see summary") }}
              </div>
              <div class="flex flex-col gap-1">
                <label for="notes">{{ t("Notes") }}</label>
                <Textarea
                  id="notes"
                  v-model="notes"
                  v-bind="notesAttrs"
                  rows="4"
                  :placeholder="t('Optional notes')"
                  :class="{ 'p-invalid': submitCount > 0 && !!errors.notes }"
                />
                <small v-if="submitCount > 0 && errors.notes" class="text-red-400 dark:text-red-300">{{ errors.notes }}</small>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>