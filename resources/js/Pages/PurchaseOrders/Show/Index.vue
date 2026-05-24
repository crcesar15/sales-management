<script setup lang="ts">
import { Card, Button, DataTable, Column, Popover, Stepper, StepList, Step, Tag } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { PurchaseOrderResponse, ProofOfPaymentMedia } from "@/Types/purchase-order-types";
import type { ReceptionOrderStatus } from "@/Types/reception-order-types";
import POStatusBadge from "../Components/POStatusBadge.vue";
import POTotalsPanel from "../Components/POTotalsPanel.vue";
import POVariantVendorsDialog from "../Components/POVariantVendorsDialog.vue";
import ReceptionStatusBadge from "@/Pages/ReceptionOrders/Components/ReceptionStatusBadge.vue";
import AdvanceStatusModal from "./Components/AdvanceStatusModal.vue";
import MarkAsPaidModal from "./Components/MarkAsPaidModal.vue";
import CancelPOModal from "./Components/CancelPOModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrder: PurchaseOrderResponse;
  proofOfPaymentMedia: ProofOfPaymentMedia | null;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDate, formatDatetime } = useDatetimeFormatter();

const advanceModalVisible = ref(false);
const markAsPaidModalVisible = ref(false);
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

function openMarkAsPaidModal() {
  markAsPaidModalVisible.value = true;
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

function goToEdit() {
  router.visit(route("purchase-orders.edit", props.purchaseOrder.id));
}

const nextAction = computed<{ label: string; status: string } | null>(() => {
  const map: Record<string, { label: string; status: string }> = {
    draft: { label: "Confirm", status: "awaiting_approval" },
    awaiting_approval: { label: "Approve", status: "approved" },
    approved: { label: "Sent", status: "sent" },
    partially_received: { label: "Mark Received", status: "received" },
  };
  return map[props.purchaseOrder.status] ?? null;
});

const advancePermission = computed(() => {
  const map: Record<string, string> = {
    draft: "purchase_order.edit",
    awaiting_approval: "purchase_order.approve",
    approved: "purchase_order.edit",
    sent: "purchase_order.edit",
    partially_received: "purchase_order.edit",
  };
  return map[props.purchaseOrder.status] ?? "";
});

const canCancel = computed(() => ["draft", "awaiting_approval", "approved"].includes(props.purchaseOrder.status));

const canMarkAsPaid = computed(() => !props.purchaseOrder.is_paid && ["approved", "sent", "partially_received", "received"].includes(props.purchaseOrder.status));

const canEdit = computed(() => props.purchaseOrder.status === "draft");

const isCancelled = computed(() => props.purchaseOrder.status === "cancelled");

const stepperSteps = [
  { key: "draft", value: "1", label: "Draft" },
  { key: "awaiting_approval", value: "2", label: "Awaiting Approval" },
  { key: "approved", value: "3", label: "Approved" },
  { key: "sent", value: "4", label: "Sent" },
  { key: "partially_received", value: "5", label: "Partially Received" },
  { key: "received", value: "6", label: "Received" },
];

const activeStepValue = computed(() => {
  if (isCancelled.value) return undefined;
  const index = stepperSteps.findIndex((s) => s.key === props.purchaseOrder.status);
  return index >= 0 ? String(index + 1) : "1";
});

const stepperPt = computed(() => {
  const pt: Record<string, Record<string, string>> = {
    root: { class: "pointer-events-none" },
    step: { class: "pointer-events-none" },
  };
  if (isCancelled.value) {
    pt.root.class += " opacity-100";
    pt.separator = { class: "!bg-red-300 dark:!bg-red-700" };
  }
  return pt;
});

const expandedRows = ref<Record<string, boolean>>({});

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

function getPaymentTermsLabel(paymentTerms: string | null | undefined): string | null {
  if (!paymentTerms) return null;
  switch (paymentTerms) {
    case "debit":
      return t("Cash");
    case "credit":
      return t("Credit");
    case "both":
      return t("Cash / Credit");
    default:
      return paymentTerms;
  }
}

function hasExpandableData(item: PurchaseOrderResponse["line_items"][number]): boolean {
  const catalog = item.catalog_entry;
  return !!(catalog?.minimum_order_quantity || catalog?.lead_time_days || catalog?.payment_terms || catalog?.details || catalog?.unit);
}

function paymentMethodLabel(type: string | null): string | null {
  if (!type) return null;
  const labels: Record<string, string> = {
    bank_transfer: t("Bank Transfer"),
    cash: t("Cash"),
    check: t("Check"),
    credit_card: t("Credit Card"),
  };
  return labels[type] ?? type;
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
}
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

    <Stepper :value="activeStepValue" :pt="stepperPt" class="mb-4 hidden xl:block">
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
            <div class="flex items-center gap-2">
              <span>{{ t("Order Details") }}</span>
              <div class="flex items-center gap-2">
                <Tag v-if="purchaseOrder.is_paid" :value="t('Paid')" severity="success" rounded class="text-xs" />
                <POStatusBadge :status="purchaseOrder.status" />
              </div>
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
                <span class="font-medium">{{ formatDatetime(purchaseOrder.created_at) }}</span>
              </div>
              <div v-if="purchaseOrder.is_paid && purchaseOrder.paid_at" class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-check-circle text-green-500 w-4 text-center" />
                  {{ t("Paid At") }}
                </span>
                <span class="font-medium text-green-600">{{ formatDatetime(purchaseOrder.paid_at) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Products") }}</template>
          <template #content>
            <DataTable v-model:expanded-rows="expandedRows" :value="purchaseOrder.line_items ?? []" data-key="id" class="mt-4 border-t-2 border-surface-200">
              <template #empty>
                {{ t("No items") }}
              </template>
              <Column expander style="width: 3rem" />
              <Column :header="t('Product')" style="min-width: 180px">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.product_variant?.product?.name ?? "---" }}</span>
                  <div class="text-sm text-surface-500">{{ data.product_variant?.name ?? data.product_variant?.identifier ?? "---" }}</div>
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
              <Column :header="t('Received')" style="min-width: 120px">
                <template #body="{ data }">
                  <span :class="Number(data.received_quantity) >= Number(data.quantity) ? 'text-green-600' : Number(data.received_quantity) > 0 ? 'text-amber-600' : 'text-surface-500'">
                    {{ formatQuantity(data.received_quantity) }} / {{ formatQuantity(data.quantity) }}
                  </span>
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
              <template #expansion="{ data }">
                <div v-if="hasExpandableData(data)" class="px-4 py-3">
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div v-if="data.catalog_entry?.minimum_order_quantity">
                      <span class="text-surface-500 block mb-1">{{ t("Min. Order") }}</span>
                      <span class="font-medium">{{ formatQuantity(data.catalog_entry.minimum_order_quantity) }}</span>
                    </div>
                    <div v-if="data.catalog_entry?.lead_time_days">
                      <span class="text-surface-500 block mb-1">{{ t("Lead Time") }}</span>
                      <span class="font-medium">{{ data.catalog_entry.lead_time_days }} {{ t("days") }}</span>
                    </div>
                    <div v-if="data.catalog_entry?.payment_terms">
                      <span class="text-surface-500 block mb-1">{{ t("Payment Terms") }}</span>
                      <span class="font-medium">{{ getPaymentTermsLabel(data.catalog_entry.payment_terms) }}</span>
                    </div>
                    <div v-if="data.catalog_entry?.details">
                      <span class="text-surface-500 block mb-1">{{ t("Details") }}</span>
                      <span class="font-medium">{{ data.catalog_entry.details }}</span>
                    </div>
                    <div v-if="data.catalog_entry?.unit">
                      <span class="text-surface-500 block mb-1">{{ t("Purchase Unit") }}</span>
                      <span class="font-medium">{{ data.catalog_entry.unit.name }}</span>
                      <span v-if="data.catalog_entry.unit.conversion_factor !== 1" class="text-surface-500 ml-1">
                        (x{{ formatQuantity(data.catalog_entry.unit.conversion_factor) }} {{ data.product_variant?.product?.measurement_unit?.name }})
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </DataTable>
          </template>
        </Card>

        <Card v-if="purchaseOrder.notes" class="mb-4">
          <template #title>{{ t("Notes") }}</template>
          <template #content>
            <p class="m-0 whitespace-pre-line text-surface-700 dark:text-surface-300">{{ purchaseOrder.notes }}</p>
          </template>
        </Card>

        <Card v-if="purchaseOrder.is_paid && purchaseOrder.proof_of_payment_type" class="mb-4">
          <template #title>{{ t("Proof of Payment Details") }}</template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div class="flex items-center gap-2">
                <i class="fa fa-credit-card text-surface-400 w-4 text-center" />
                <span class="text-sm text-surface-500">{{ t("Payment Method") }}:</span>
                <span class="font-medium">{{ paymentMethodLabel(purchaseOrder.proof_of_payment_type) }}</span>
              </div>
              <div v-if="purchaseOrder.proof_of_payment_number" class="flex items-center gap-2">
                <i class="fa fa-hashtag text-surface-400 w-4 text-center" />
                <span class="text-sm text-surface-500">{{ t("Reference Number") }}:</span>
                <span class="font-medium">{{ purchaseOrder.proof_of_payment_number }}</span>
              </div>
              <div v-if="proofOfPaymentMedia" class="flex items-center gap-2">
                <i class="fa fa-file text-surface-400 w-4 text-center" />
                <span class="text-sm text-surface-500">{{ t("Proof of Payment") }}:</span>
                <a :href="proofOfPaymentMedia.url" target="_blank" class="text-primary-500 hover:underline flex items-center gap-1">
                  <i class="fa fa-download text-xs" />
                  {{ proofOfPaymentMedia.file_name }}
                  <span class="text-xs text-surface-400">({{ formatFileSize(proofOfPaymentMedia.size) }})</span>
                </a>
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="purchaseOrder.reception_orders?.length" class="mb-4">
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ t("Receptions") }}</span>
              <Button
                v-can="'reception_order.create'"
                :label="t('Create Reception Order')"
                icon="fa fa-add"
                size="small"
                @click="router.visit(route('reception-orders.create'))"
              />
            </div>
          </template>
          <template #content>
            <DataTable :value="purchaseOrder.reception_orders" data-key="id" striped-rows row-hover>
              <Column field="id" :header="t('#')" style="min-width: 60px">
                <template #body="{ data }">
                  <a class="text-primary-500 hover:underline cursor-pointer" @click="router.visit(route('reception-orders.show', data.id))">
                    #{{ data.id }}
                  </a>
                </template>
              </Column>
              <Column :header="t('Reception Date')" style="min-width: 120px">
                <template #body="{ data }">
                  {{ formatDate(data.reception_date) }}
                </template>
              </Column>
              <Column :header="t('Store')" style="min-width: 120px">
                <template #body="{ data }">
                  {{ data.store?.name ?? "—" }}
                </template>
              </Column>
              <Column :header="t('Status')" style="min-width: 120px">
                <template #body="{ data }">
                  <ReceptionStatusBadge :status="data.status as ReceptionOrderStatus" />
                </template>
              </Column>
              <Column :header="t('Created By')" style="min-width: 120px">
                <template #body="{ data }">
                  {{ data.user?.full_name ?? "—" }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <POTotalsPanel
          :sub-total="purchaseOrder.sub_total ?? 0"
          :discount="purchaseOrder.discount ?? 0"
          :total="purchaseOrder.total ?? 0"
          :can-edit="canEdit"
          :can-cancel="canCancel"
          :can-mark-as-paid="canMarkAsPaid"
          @edit="goToEdit"
          @cancel="cancelModalVisible = true"
          @mark-as-paid="openMarkAsPaidModal()"
        />
      </div>
    </div>

    <AdvanceStatusModal v-model:visible="advanceModalVisible" :purchase-order-id="purchaseOrder.id" :target-status="targetStatus" :is-fully-received="purchaseOrder.is_fully_received" />

    <MarkAsPaidModal v-model:visible="markAsPaidModalVisible" :purchase-order-id="purchaseOrder.id" />

    <CancelPOModal v-model:visible="cancelModalVisible" :purchase-order-id="purchaseOrder.id" />

    <POVariantVendorsDialog
      v-model:visible="variantVendorsVisible"
      :product-variant-id="selectedVariantId"
      :product-name="selectedVariantName"
      :variant-label="selectedVariantLabel"
    />
  </div>
</template>
