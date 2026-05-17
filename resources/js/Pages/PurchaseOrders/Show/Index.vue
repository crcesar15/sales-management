<script setup lang="ts">
import { Card, Button, DataTable, Column, Popover, Stepper, StepList, Step } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useAuth } from "@/Composables/useAuth";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { PurchaseOrderResponse } from "@/Types/purchase-order-types";
import POStatusBadge from "../Components/POStatusBadge.vue";
import POTotalsPanel from "../Components/POTotalsPanel.vue";
import POVariantVendorsDialog from "../Components/POVariantVendorsDialog.vue";
import AdvanceStatusModal from "./Components/AdvanceStatusModal.vue";
import CancelPOModal from "./Components/CancelPOModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrder: PurchaseOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();

const advanceModalVisible = ref(false);
const cancelModalVisible = ref(false);
const targetStatus = ref("");

const vendorInfoPopover = ref();
const variantVendorsVisible = ref(false);
const selectedVariantId = ref<number | null>(null);
const selectedVariantName = ref("");
const selectedVariantLabel = ref("");

function openAdvanceModal(status: string) {
  targetStatus.value = status;
  advanceModalVisible.value = true;
}

function toggleVendorInfo(event: Event) {
  vendorInfoPopover.value.toggle(event);
}

function openVariantVendors(productVariantId: number, productName: string, variantLabel: string) {
  selectedVariantId.value = productVariantId;
  selectedVariantName.value = productName;
  selectedVariantLabel.value = variantLabel;
  variantVendorsVisible.value = true;
}

function formatDate(date: string | null): string {
  if (!date) return "---";
  return useDatetimeFormatter(date, getSetting("general", "date_format") ?? "YYYY-MM-DD");
}

function formatDateTime(date: string | null): string {
  if (!date) return "---";
  return useDatetimeFormatter(date);
}

function goToEdit() {
  router.visit(route("purchase-orders.edit", props.purchaseOrder.id));
}

const nextAction = computed<{ label: string; status: string } | null>(() => {
  const map: Record<string, { label: string; status: string }> = {
    draft: { label: "Confirm", status: "awaiting_approval" },
    awaiting_approval: { label: "Approve", status: "approved" },
    approved: { label: "Sent", status: "sent" },
    sent: { label: "Pay", status: "paid" },
  };
  return map[props.purchaseOrder.status] ?? null;
});

const advancePermission = computed(() => {
  const map: Record<string, string> = {
    draft: "purchase_order.edit",
    awaiting_approval: "purchase_order.approve",
    approved: "purchase_order.edit",
    sent: "purchase_order.edit",
  };
  return map[props.purchaseOrder.status] ?? "";
});

const canCancel = computed(() => ["draft", "awaiting_approval", "approved"].includes(props.purchaseOrder.status));

const isCancelled = computed(() => props.purchaseOrder.status === "cancelled");

const stepperSteps = [
  { key: "draft", value: "1", label: "Draft" },
  { key: "awaiting_approval", value: "2", label: "Awaiting Approval" },
  { key: "approved", value: "3", label: "Approved" },
  { key: "sent", value: "4", label: "Sent" },
  { key: "paid", value: "5", label: "Paid" },
];

const activeStepValue = computed(() => {
  if (isCancelled.value) return undefined;
  const index = stepperSteps.findIndex((s) => s.key === props.purchaseOrder.status);
  return index >= 0 ? String(index + 1) : "1";
});

const cancelledPt = computed(() =>
  isCancelled.value ? { root: { class: "opacity-100" }, separator: { class: "!bg-red-300 dark:!bg-red-700" } } : {},
);
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="router.visit(route('purchase-orders'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Purchase Order") }} #{{ purchaseOrder.id }}</h2>
      </div>
      <div v-if="nextAction" class="flex items-center gap-2">
        <Button
          v-can="advancePermission"
          :label="t(nextAction.label)"
          icon="fa fa-arrow-right"
          @click="openAdvanceModal(nextAction.status)"
        />
      </div>
    </div>

    <Stepper :value="activeStepValue" :pt="cancelledPt" class="mb-4 hidden xl:block">
      <StepList>
        <Step v-for="step in stepperSteps" :key="step.key" :value="step.value" :class="{ '!text-red-500': isCancelled }">
          {{ t(step.label) }}
        </Step>
      </StepList>
    </Stepper>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card class="mb-4">
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ t("Order Details") }}</span>
              <POStatusBadge :status="purchaseOrder.status" />
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
                  <span class="font-medium">{{ purchaseOrder.vendor?.fullname ?? "---" }}</span>
                  <Button
                    v-if="purchaseOrder.vendor?.id"
                    v-tooltip.top="t('Vendor Information')"
                    icon="fa fa-eye"
                    text
                    size="small"
                    @click="toggleVendorInfo"
                  />
                </div>
                <Popover ref="vendorInfoPopover">
                  <div v-if="purchaseOrder.vendor" class="p-4 w-72">
                    <h4 class="text-lg font-semibold mb-3">{{ purchaseOrder.vendor.fullname }}</h4>
                    <div class="flex flex-col gap-2 text-sm">
                      <div v-if="purchaseOrder.vendor.email" class="flex items-center gap-2">
                        <i class="fa fa-envelope text-surface-400 w-4 text-center" />
                        <a :href="'mailto:' + purchaseOrder.vendor.email" class="text-primary-500 hover:underline">
                          {{ purchaseOrder.vendor.email }}
                        </a>
                      </div>
                      <div v-if="purchaseOrder.vendor.phone" class="flex items-center gap-2">
                        <i class="fa fa-phone text-surface-400 w-4 text-center" />
                        <a :href="'tel:' + purchaseOrder.vendor.phone" class="text-primary-500 hover:underline">
                          {{ purchaseOrder.vendor.phone }}
                        </a>
                      </div>
                      <div v-if="purchaseOrder.vendor.address" class="flex items-start gap-2">
                        <i class="fa fa-location-dot text-surface-400 w-4 text-center mt-0.5" />
                        <span>{{ purchaseOrder.vendor.address }}</span>
                      </div>
                      <div v-if="purchaseOrder.vendor.details" class="flex items-start gap-2">
                        <i class="fa fa-circle-info text-surface-400 w-4 text-center mt-0.5" />
                        <span class="text-surface-500">{{ purchaseOrder.vendor.details }}</span>
                      </div>
                    </div>
                    <div
                      v-if="purchaseOrder.vendor.additional_contacts?.length"
                      class="mt-3 pt-3 border-t border-surface-200 dark:border-surface-700"
                    >
                      <p class="text-xs font-medium text-surface-500 uppercase mb-2">{{ t("Contacts") }}</p>
                      <div class="flex flex-col gap-2 text-sm">
                        <div v-for="contact in purchaseOrder.vendor.additional_contacts" :key="contact.email" class="flex flex-col">
                          <span class="font-medium">
                            {{ contact.name }}
                            <span class="text-surface-400 font-normal text-xs">{{ contact.role }}</span>
                          </span>
                          <span class="text-surface-500 text-xs">{{ contact.email }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </Popover>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-user text-surface-400 w-4 text-center" />
                  {{ t("Created By") }}
                </span>
                <span class="font-medium">{{ purchaseOrder.user?.full_name ?? "---" }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-calendar text-surface-400 w-4 text-center" />
                  {{ t("Order Date") }}
                </span>
                <span class="font-medium">{{ formatDate(purchaseOrder.order_date) }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-truck text-surface-400 w-4 text-center" />
                  {{ t("Expected Arrival Date") }}
                </span>
                <span class="font-medium">{{ formatDate(purchaseOrder.expected_arrival_date) }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-clock text-surface-400 w-4 text-center" />
                  {{ t("Created At") }}
                </span>
                <span class="font-medium">{{ formatDateTime(purchaseOrder.created_at) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Line Items") }}</template>
          <template #content>
            <DataTable :value="purchaseOrder.line_items ?? []" class="mt-4 border-t-2 border-surface-200">
              <template #empty>
                {{ t("No items") }}
              </template>
              <Column :header="t('Product')" style="min-width: 180px">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.product_variant?.product?.name ?? "---" }}</span>
                  <div class="text-sm text-surface-500">{{ data.product_variant?.name ?? data.product_variant?.identifier ?? "---" }}</div>
                </template>
              </Column>
              <Column :header="t('Quantity')" style="min-width: 90px">
                <template #body="{ data }">
                  {{ data.quantity }}
                </template>
              </Column>
              <Column :header="t('Unit Price')" style="min-width: 120px">
                <template #body="{ data }">
                  {{ formatCurrency(String(data.price)) }}
                </template>
              </Column>
              <Column :header="t('Line Total')" style="min-width: 120px">
                <template #body="{ data }">
                  <span class="font-medium">{{ formatCurrency(String(data.total)) }}</span>
                </template>
              </Column>
              <Column :header="t('Actions')" style="min-width: 60px">
                <template #body="{ data }">
                  <Button
                    v-tooltip.top="t('View Vendors')"
                    icon="fa fa-store"
                    text
                    size="small"
                    @click="
                      openVariantVendors(
                        data.product_variant_id,
                        data.product_variant?.product?.name ?? '',
                        data.product_variant?.name ?? data.product_variant?.identifier ?? '',
                      )
                    "
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <Card v-if="purchaseOrder.notes" class="mb-4">
          <template #title>{{ t("Notes") }}</template>
          <template #content>
            <p class="m-0 whitespace-pre-line text-surface-700 dark:text-surface-300">{{ purchaseOrder.notes }}</p>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <POTotalsPanel
          :sub-total="purchaseOrder.sub_total ?? 0"
          :discount="purchaseOrder.discount ?? 0"
          :total="purchaseOrder.total ?? 0"
          :can-cancel="canCancel"
          @edit="goToEdit"
          @cancel="cancelModalVisible = true"
        />
      </div>
    </div>

    <AdvanceStatusModal v-model:visible="advanceModalVisible" :purchase-order-id="purchaseOrder.id" :target-status="targetStatus" />

    <CancelPOModal v-model:visible="cancelModalVisible" :purchase-order-id="purchaseOrder.id" />

    <POVariantVendorsDialog
      v-model:visible="variantVendorsVisible"
      :product-variant-id="selectedVariantId"
      :product-name="selectedVariantName"
      :variant-label="selectedVariantLabel"
    />
  </div>
</template>
