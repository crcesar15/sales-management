<script setup lang="ts">
import { Button, Card, DatePicker, Select, Textarea, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string, date } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import ReceptionLineItemsTable from "../Components/ReceptionLineItems.vue";
import type { ReceptionLineItem } from "../Components/ReceptionLineItems.vue";
import type { ReceptionOrderResponse } from "@/Types/reception-order-types";
import POStatusBadge from "../../PurchaseOrders/Components/POStatusBadge.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  receptionOrder: ReceptionOrderResponse;
  stores: Array<{ id: number; name: string }>;
}>();

const toast = useToast();
const { t } = useI18n();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));

const schema = toTypedSchema(
  object({
    store_id: number().required().typeError(t("Store is required")),
    reception_date: date().nullable().optional(),
    notes: string().nullable().optional().max(1000, t("Notes must not exceed 1000 characters")),
  }),
);

const { handleSubmit, errors, values, defineField, setFieldValue, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    store_id: props.receptionOrder.store_id,
    reception_date: props.receptionOrder.reception_date ? new Date(props.receptionOrder.reception_date) : null,
    notes: props.receptionOrder.notes ?? null,
  },
});

const [receptionDate, receptionDateAttrs] = defineField("reception_date");
const [notes, notesAttrs] = defineField("notes");

// Build a map of PO line item remaining quantities by PO line item id
const poRemainingByItem = new Map<number, number>();
if (props.receptionOrder.purchase_order?.line_items) {
  for (const poItem of props.receptionOrder.purchase_order.line_items) {
    poRemainingByItem.set(poItem.id, Number(poItem.remaining_quantity ?? poItem.quantity));
  }
}

const lineItems = ref<ReceptionLineItem[]>(
  props.receptionOrder.line_items.map((item) => ({
    id: crypto.randomUUID(),
    purchase_order_item_id: item.purchase_order_item_id,
    product_variant_id: item.product_variant_id,
    product_name: item.product_variant?.product?.name ?? "—",
    variant_label: item.product_variant?.name ?? item.product_variant?.identifier ?? "—",
    quantity: Number(item.quantity),
    max_quantity: poRemainingByItem.get(item.purchase_order_item_id) ?? undefined,
    expiry_date: item.expiry_date ? new Date(item.expiry_date) : null,
    batch_identifier: item.batch_identifier ?? '',
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
  })),
);

const itemsError = ref("");

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  if (lineItems.value.length === 0) {
    itemsError.value = t("At least one item is required");
    return;
  }

  const payload = {
    store_id: formValues.store_id,
    reception_date: formValues.reception_date ? formValues.reception_date.toISOString().split("T")[0] : null,
    notes: formValues.notes || null,
    items: lineItems.value.map((item) => ({
      purchase_order_item_id: item.purchase_order_item_id,
      product_variant_id: item.product_variant_id,
      quantity: Number(item.quantity),
      expiry_date: item.expiry_date ? item.expiry_date.toISOString().split("T")[0] : null,
      batch_identifier: item.batch_identifier || null,
    })),
  };

  router.put(route("reception-orders.update", props.receptionOrder.id), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Reception order updated successfully"), life: 3000 });
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
  router.visit(route("reception-orders.show", props.receptionOrder.id));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Edit Reception Order") }} #{{ receptionOrder.id }}</h2>
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
                <label>{{ t("Purchase Order") }}</label>
                <div class="flex items-center gap-2">
                  <a
                    v-if="receptionOrder.purchase_order?.id"
                    class="text-primary-500 hover:underline cursor-pointer font-medium"
                    :href="route('purchase-orders.show', receptionOrder.purchase_order.id)"
                    target="_blank"
                  >
                    #{{ receptionOrder.purchase_order.id }}
                  </a>
                  <span v-else class="font-medium">—</span>
                  <POStatusBadge v-if="receptionOrder.purchase_order" :status="receptionOrder.purchase_order.status" />
                  <span class="text-sm text-surface-500">({{ t("cannot be changed") }})</span>
                </div>
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
                  <small v-if="submitCount > 0 && errors.reception_date" class="text-red-400 dark:text-red-300">
                    {{ errors.reception_date }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <ReceptionLineItemsTable v-model="lineItems" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex justify-between text-sm">
                <span class="text-surface-500">{{ t("Total Items") }}</span>
                <span class="font-medium">{{ lineItems.length }}</span>
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
