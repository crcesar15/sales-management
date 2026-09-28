<script setup lang="ts">
import { Badge, Button, Card, Divider, Popover } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { ReceptionOrderResponse } from "@/Types/reception-order-types";
import ReceptionStatusBadge from "../Components/ReceptionStatusBadge.vue";
import POStatusBadge from "../../PurchaseOrders/Components/POStatusBadge.vue";
import ReceptionStatusStepper from "./Components/ReceptionStatusStepper.vue";
import CompleteReceptionModal from "./Components/CompleteReceptionModal.vue";
import CancelReceptionModal from "./Components/CancelReceptionModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  receptionOrder: ReceptionOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDate, formatDatetime } = useDatetimeFormatter();

const completeModalVisible = ref(false);
const cancelModalVisible = ref(false);

const vendorInfoPopover = ref();

const canEdit = computed(() => props.receptionOrder.status === "pending");
const canComplete = computed(() => props.receptionOrder.status === "pending");
const canCancel = computed(() => props.receptionOrder.status === "pending");

function toggleVendorInfo(event: Event) {
  vendorInfoPopover.value.toggle(event);
}

function goToEdit() {
  router.visit(route("reception-orders.edit", props.receptionOrder.id));
}

function productName(item: ReceptionOrderResponse["line_items"][number]): string {
  return item.product_variant?.product?.name ?? "—";
}

function variantLabel(item: ReceptionOrderResponse["line_items"][number]): string {
  return item.product_variant?.name ?? item.product_variant?.identifier ?? productName(item);
}

function hasVariantLabel(item: ReceptionOrderResponse["line_items"][number]): boolean {
  return variantLabel(item) !== productName(item);
}

function purchaseUnitLabel(item: ReceptionOrderResponse["line_items"][number]): string {
  return item.catalog_entry?.unit?.name ?? item.product_variant?.product?.measurement_unit?.name ?? "—";
}

function formatQuantity(q: number | string | null | undefined): string {
  if (q === null || q === undefined) return "0";
  return String(parseFloat(String(q)));
}

function conversionLabel(item: ReceptionOrderResponse["line_items"][number]): string | null {
  const baseUnit = item.product_variant?.product?.measurement_unit?.name;
  const conversionFactor = item.catalog_entry?.unit?.conversion_factor ?? 1;

  if (!baseUnit || conversionFactor === 1) return null;

  return `1 ${purchaseUnitLabel(item)} = ${String(conversionFactor)} ${baseUnit}`;
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text severity="secondary" @click="router.visit(route('reception-orders'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Reception Order") }} #{{ receptionOrder.id }}</h2>
      </div>
      <div v-if="canComplete" v-can="'reception_order.manage'" class="flex items-center gap-2">
        <Button :label="t('Complete')" icon="fa fa-check" severity="warning" @click="completeModalVisible = true" />
      </div>
    </div>

    <ReceptionStatusStepper :current-status="receptionOrder.status" />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card class="mb-4">
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ t("Reception Details") }}</span>
              <ReceptionStatusBadge :status="receptionOrder.status" />
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-building text-surface-400 w-4 text-center" />
                  {{ t("Vendor") }}
                </span>
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ receptionOrder.vendor?.fullname ?? "—" }}</span>
                  <Button
                    v-if="receptionOrder.vendor?.id"
                    v-tooltip.top="t('Vendor Information')"
                    icon="fa fa-eye"
                    text
                    size="small"
                    @click="toggleVendorInfo"
                  />
                </div>
                <Popover ref="vendorInfoPopover">
                  <div v-if="receptionOrder.vendor" class="p-4 w-72">
                    <h4 class="text-lg font-semibold mb-3">{{ receptionOrder.vendor.fullname }}</h4>
                    <div class="flex flex-col gap-2 text-sm">
                      <div v-if="receptionOrder.vendor.email" class="flex items-center gap-2">
                        <i class="fa fa-envelope text-surface-400 w-4 text-center" />
                        <a :href="'mailto:' + receptionOrder.vendor.email" class="text-primary-500 hover:underline">
                          {{ receptionOrder.vendor.email }}
                        </a>
                      </div>
                      <div v-if="receptionOrder.vendor.phone" class="flex items-center gap-2">
                        <i class="fa fa-phone text-surface-400 w-4 text-center" />
                        <a :href="'tel:' + receptionOrder.vendor.phone" class="text-primary-500 hover:underline">
                          {{ receptionOrder.vendor.phone }}
                        </a>
                      </div>
                      <div v-if="receptionOrder.vendor.address" class="flex items-start gap-2">
                        <i class="fa fa-location-dot text-surface-400 w-4 text-center mt-0.5" />
                        <span>{{ receptionOrder.vendor.address }}</span>
                      </div>
                    </div>
                  </div>
                </Popover>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-store text-surface-400 w-4 text-center" />
                  {{ t("Store") }}
                </span>
                <span class="font-medium">{{ receptionOrder.store?.name ?? "—" }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-file-invoice text-surface-400 w-4 text-center" />
                  {{ t("Purchase Order") }}
                </span>
                <div v-if="receptionOrder.purchase_order_id" class="flex items-center gap-2">
                  <a
                    class="text-primary-500 hover:underline cursor-pointer font-medium"
                    :href="route('purchase-orders.show', receptionOrder.purchase_order_id)"
                    target="_blank"
                  >
                    #{{ receptionOrder.purchase_order_id }}
                  </a>
                  <POStatusBadge v-if="receptionOrder.purchase_order" :status="receptionOrder.purchase_order.status" />
                </div>
                <span v-else>—</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-user text-surface-400 w-4 text-center" />
                  {{ t("Created By") }}
                </span>
                <span class="font-medium">{{ receptionOrder.user?.full_name ?? "—" }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-calendar text-surface-400 w-4 text-center" />
                  {{ t("Reception Date") }}
                </span>
                <span class="font-medium">{{ formatDate(receptionOrder.reception_date) }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-clock text-surface-400 w-4 text-center" />
                  {{ t("Created At") }}
                </span>
                <span class="font-medium">{{ formatDatetime(receptionOrder.created_at) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4 !border !border-surface-200 !shadow-none dark:!border-surface-700">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <div
              v-if="receptionOrder.line_items.length === 0"
              class="flex flex-col items-center justify-center border-y border-surface-200 py-10 text-surface-500 dark:border-surface-700 dark:text-surface-400"
            >
              <i class="fa fa-box-open mb-3 text-4xl" aria-hidden="true"></i>
              <span class="text-lg font-medium">{{ t("No items") }}</span>
            </div>

            <div v-else class="2xl:border 2xl:border-surface-200 dark:2xl:border-surface-700">
              <div
                aria-hidden="true"
                class="hidden 2xl:grid 2xl:grid-cols-[minmax(8rem,1.35fr)_minmax(9rem,1.1fr)_minmax(7rem,0.7fr)_minmax(10rem,1.1fr)_minmax(10rem,1.1fr)_minmax(6rem,0.7fr)] 2xl:items-center 2xl:gap-3 2xl:border-b 2xl:border-surface-200 2xl:bg-surface-100 2xl:px-3 2xl:py-2 2xl:text-sm 2xl:font-semibold dark:2xl:border-surface-700 dark:2xl:bg-surface-800"
              >
                <span>{{ t("Product") }}</span>
                <span>{{ t("Purchase Unit") }}</span>
                <span>{{ t("Quantity") }}</span>
                <span>{{ t("Expiry Date") }}</span>
                <span>{{ t("Batch Identifier") }}</span>
                <span>{{ t("Line Total") }}</span>
              </div>

              <ul class="flex flex-col gap-3 2xl:block 2xl:divide-y 2xl:divide-surface-200 dark:2xl:divide-surface-700">
                <li
                  v-for="item in receptionOrder.line_items"
                  :key="item.id"
                  class="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-800/60 2xl:rounded-none 2xl:border-0 2xl:bg-transparent 2xl:px-3 2xl:py-3 dark:2xl:bg-transparent"
                >
                  <div
                    class="grid grid-cols-2 gap-x-3 gap-y-4 2xl:grid-cols-[minmax(8rem,1.35fr)_minmax(9rem,1.1fr)_minmax(7rem,0.7fr)_minmax(10rem,1.1fr)_minmax(10rem,1.1fr)_minmax(6rem,0.7fr)] 2xl:items-start 2xl:gap-3"
                  >
                    <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
                      <span class="block truncate text-base font-semibold text-surface-900 dark:text-surface-50">{{ productName(item) }}</span>
                      <Badge v-if="hasVariantLabel(item)" :value="variantLabel(item)" severity="secondary" class="mt-1 w-fit max-w-full truncate" />
                    </div>

                    <div class="col-span-2 flex min-w-0 flex-col gap-1 2xl:col-span-1 2xl:pt-1">
                      <span class="font-medium">{{ purchaseUnitLabel(item) }}</span>
                      <span v-if="conversionLabel(item)" class="text-sm text-surface-500 dark:text-surface-400">{{ conversionLabel(item) }}</span>
                    </div>

                    <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
                      <span class="block text-base font-medium 2xl:sr-only">{{ t("Quantity") }}</span>
                      <span class="block tabular-nums">{{ formatQuantity(item.quantity) }}</span>
                    </div>

                    <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
                      <span class="block text-base font-medium 2xl:sr-only">{{ t("Expiry Date") }}</span>
                      <span class="block">{{ item.expiry_date ? formatDate(item.expiry_date) : "—" }}</span>
                    </div>

                    <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
                      <span class="block text-base font-medium 2xl:sr-only">{{ t("Batch Identifier") }}</span>
                      <span class="block break-words">{{ item.batch_identifier || "—" }}</span>
                    </div>

                    <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
                      <span class="block text-base font-medium 2xl:sr-only">{{ t("Line Total") }}</span>
                      <span class="block font-semibold tabular-nums">{{ formatCurrency(String(item.total)) }}</span>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </template>
        </Card>

        <Card v-if="receptionOrder.notes" class="mb-4">
          <template #title>{{ t("Notes") }}</template>
          <template #content>
            <p class="m-0 whitespace-pre-line text-surface-700 dark:text-surface-300">{{ receptionOrder.notes }}</p>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div class="flex justify-between text-sm">
                <span class="text-surface-500">{{ t("Store") }}</span>
                <span class="font-medium">{{ receptionOrder.store?.name ?? "—" }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-surface-500">{{ t("Total Items") }}</span>
                <span class="font-medium">{{ receptionOrder.line_items?.length ?? 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-surface-500">{{ t("Reception Date") }}</span>
                <span class="font-medium">{{ formatDate(receptionOrder.reception_date) }}</span>
              </div>
              <Divider class="!my-1" />
              <div class="flex flex-col gap-2">
                <Button
                  v-if="canEdit"
                  v-can="'reception_order.edit'"
                  icon="fa fa-pen"
                  :label="t('Edit')"
                  class="flex-1"
                  @click="goToEdit"
                />
                <Button
                  v-if="canCancel"
                  v-can="'reception_order.manage'"
                  icon="fa fa-ban"
                  :label="t('Cancel')"
                  severity="secondary"
                  class="flex-1"
                  @click="cancelModalVisible = true"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <CompleteReceptionModal v-model:visible="completeModalVisible" :reception-order-id="receptionOrder.id" />
    <CancelReceptionModal v-model:visible="cancelModalVisible" :reception-order-id="receptionOrder.id" />
  </div>
</template>
