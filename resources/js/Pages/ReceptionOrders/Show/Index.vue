<script setup lang="ts">
import { Card, Button, DataTable, Column, Divider, Popover, Tag } from "primevue";
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

function getStockSeverity(stock: number | null | undefined, minStock: number | null | undefined): "success" | "warn" | "danger" {
  if (stock === null || stock === undefined) return "success";
  if (stock === 0) return "danger";
  if (minStock && stock <= minStock) return "warn";
  return "success";
}

function getStockLabel(stock: number | null | undefined): string {
  if (stock === null || stock === undefined) return "—";
  if (stock === 0) return t("Out of stock");
  return `${t("In stock")}: ${String(stock)}`;
}

function formatQuantity(q: number | string | null | undefined): string {
  if (q === null || q === undefined) return "0";
  return String(parseFloat(String(q)));
}

function formatConversion(item: ReceptionOrderResponse["line_items"][number]): string {
  if (!item.catalog_entry?.unit || item.catalog_entry.unit.conversion_factor <= 1) return "";
  const baseName =
    item.product_variant?.product?.measurement_unit?.abbreviation ?? item.product_variant?.product?.measurement_unit?.name ?? t("units");
  return `1 ${item.catalog_entry.unit.name} = ${item.catalog_entry.unit.conversion_factor} ${baseName}`;
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

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <DataTable :value="receptionOrder.line_items" data-key="id" class="mt-4 border-t-2 border-surface-200 dark:border-surface-700">
              <template #empty>
                {{ t("No items") }}
              </template>
              <Column :header="t('Product')" style="min-width: 180px">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.product_variant?.product?.name ?? "—" }}</span>
                  <div class="text-sm text-surface-500">{{ data.product_variant?.name ?? data.product_variant?.identifier ?? "—" }}</div>
                </template>
              </Column>
              <Column :header="t('Stock')" style="min-width: 90px">
                <template #body="{ data }">
                  <Tag
                    :value="getStockLabel(data.product_variant?.stock)"
                    :severity="getStockSeverity(data.product_variant?.stock, data.product_variant?.minimum_stock_level)"
                    class="text-xs"
                    rounded
                  />
                </template>
              </Column>
              <Column :header="t('Quantity')" style="min-width: 90px">
                <template #body="{ data }">
                  {{ formatQuantity(data.quantity) }}
                </template>
              </Column>
              <Column :header="t('Conversion')" style="min-width: 160px">
                <template #body="{ data }">
                  <span v-if="formatConversion(data)" class="text-sm text-surface-500">{{ formatConversion(data) }}</span>
                  <span v-else class="text-surface-500">{{ data.product_variant?.product?.measurement_unit?.abbreviation ?? data.product_variant?.product?.measurement_unit?.name ?? t("units") }}</span>
                </template>
              </Column>
              <Column :header="t('Expiry Date')" style="min-width: 120px">
                <template #body="{ data }">
                  {{ data.expiry_date ? formatDate(data.expiry_date) : "—" }}
                </template>
              </Column>
              <Column :header="t('Batch Identifier')" style="min-width: 160px">
                <template #body="{ data }">
                  {{ data.batch_identifier || "—" }}
                </template>
              </Column>
              <Column :header="t('Line Total')" style="min-width: 120px">
                <template #body="{ data }">
                  <span class="font-semibold tabular-nums">{{ formatCurrency(String(data.total)) }}</span>
                </template>
              </Column>
            </DataTable>
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
              <div class="flex gap-2">
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
