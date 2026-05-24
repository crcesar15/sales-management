<script setup lang="ts">
import { Button, Card, DatePicker, Select, Popover, useToast, ConfirmDialog } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string, date } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import POLineItemsTable from "../Components/POLineItemsTable.vue";
import POFinancialSummary from "../Components/POFinancialSummary.vue";
import type { LineItem } from "../Components/POLineItemsTable.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendors: Array<{
    id: number;
    fullname: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    details: string | null;
    additional_contacts: AdditionalContact[] | null;
  }>;
}>();

interface AdditionalContact {
  name: string;
  role: string;
  email: string;
  phone: string;
}

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
    vendor_id: undefined as unknown as number,
    order_date: undefined as Date | undefined,
    expected_arrival_date: null as Date | null,
    discount: 0 as number | null,
    notes: null as string | null,
  },
});

const [orderDate, orderDateAttrs] = defineField("order_date");
const [expectedArrivalDate, expectedArrivalDateAttrs] = defineField("expected_arrival_date");
const [discount, discountAttrs] = defineField("discount");
const [notes, notesAttrs] = defineField("notes");

const lineItems = ref<LineItem[]>([]);
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
      catalog_id: item.catalog_id,
      unit_id: item.unit_id ?? null,
      quantity: Number(item.quantity),
      price: Number(item.price),
    })),
  };

  router.post(route("purchase-orders.store"), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order created successfully"), life: 3000 });
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
  router.visit(route("purchase-orders"));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Create Purchase Order") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="false" @click="submit" />
    </div>

    <ConfirmDialog />

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-1">
                <label for="vendor">
                  {{ t("Vendor") }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex">
                  <div class="flex-1">
                    <Select
                      id="vendor"
                      :model-value="values.vendor_id"
                      :options="vendorOptions"
                      option-label="name"
                      option-value="value"
                      :placeholder="t('Select a Vendor')"
                      :class="{ 'p-invalid': submitCount > 0 && !!errors.vendor_id }"
                      filter
                      class="w-full"
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
                  <div v-if="selectedVendor" class="p-4 w-72">
                    <h4 class="text-lg font-semibold mb-3">{{ selectedVendor.fullname }}</h4>
                    <div class="flex flex-col gap-2 text-sm">
                      <div v-if="selectedVendor.email" class="flex items-center gap-2">
                        <i class="fa fa-envelope text-surface-400 w-4 text-center" />
                        <span>{{ selectedVendor.email }}</span>
                      </div>
                      <div v-if="selectedVendor.phone" class="flex items-center gap-2">
                        <i class="fa fa-phone text-surface-400 w-4 text-center" />
                        <span>{{ selectedVendor.phone }}</span>
                      </div>
                      <div v-if="selectedVendor.address" class="flex items-start gap-2">
                        <i class="fa fa-location-dot text-surface-400 w-4 text-center mt-0.5" />
                        <span>{{ selectedVendor.address }}</span>
                      </div>
                      <div v-if="selectedVendor.details" class="flex items-start gap-2">
                        <i class="fa fa-circle-info text-surface-400 w-4 text-center mt-0.5" />
                        <span class="text-surface-500">{{ selectedVendor.details }}</span>
                      </div>
                    </div>
                    <div v-if="selectedVendor.additional_contacts?.length" class="mt-3 pt-3 border-t border-surface-200 dark:border-surface-700">
                      <p class="text-xs font-medium text-surface-500 uppercase mb-2">{{ t("Contacts") }}</p>
                      <div class="flex flex-col gap-2 text-sm">
                        <div v-for="contact in selectedVendor.additional_contacts" :key="contact.email" class="flex flex-col">
                          <span class="font-medium">{{ contact.name }} <span class="text-surface-400 font-normal text-xs">{{ contact.role }}</span></span>
                          <span class="text-surface-500 text-xs">{{ contact.email }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </Popover>
              </div>
              <div class="grid grid-cols-2 gap-4">
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
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <POLineItemsTable v-model="lineItems" :vendor-id="selectedVendor?.id ?? null" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-4 col-span-12">
        <POFinancialSummary
          :sub-total="subTotal"
          :total="total"
          :discount="discount"
          :discount-attrs="discountAttrs"
          :max-discount="subTotal"
          :notes="notes"
          :notes-attrs="notesAttrs"
          :submit-count="submitCount"
          :errors="errors"
          @update:discount="setFieldValue('discount', $event)"
          @update:notes="setFieldValue('notes', $event)"
        />
      </div>
    </div>
  </div>
</template>
