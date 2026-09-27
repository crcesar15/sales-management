<script setup lang="ts">
import { AutoComplete, Badge, Tag, useToast } from "primevue";
import { nextTick, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { usePurchaseOrderClient } from "@composables/usePurchaseOrderClient";
import type { VendorCatalogEntry } from "@/Types/catalog-types";

interface SearchOption extends VendorCatalogEntry {
  search_label: string;
}

const props = defineProps<{
  vendorId: number | null;
}>();

const emit = defineEmits<{
  select: [entry: VendorCatalogEntry];
}>();

const { t } = useI18n();
const toast = useToast();
const { formatCurrency } = useCurrencyFormatter();
const { fetchVendorCatalogApi } = usePurchaseOrderClient();
const searchResults = ref<SearchOption[]>([]);
const selectedEntry = ref<SearchOption | null>(null);
const searchLoading = ref(false);
const productSearch = ref<{ hide: () => void } | null>(null);

function productLabel(entry: VendorCatalogEntry): string {
  const productName = entry.product_variant?.product?.name ?? entry.product_variant?.name ?? t("Unknown product");
  const variantName = variantLabel(entry);

  return variantName ? `${productName} ${variantName}` : productName;
}

function variantLabel(entry: VendorCatalogEntry): string | null {
  return entry.product_variant?.name || entry.product_variant?.identifier || null;
}

function purchaseUnitLabel(entry: VendorCatalogEntry): string {
  const purchaseUnit = entry.purchase_unit;
  const baseUnit = entry.product_variant?.product?.measurement_unit;
  const baseUnitName = baseUnit?.name;
  const baseUnitLabel = baseUnit?.abbreviation ?? baseUnit?.name;

  if (!purchaseUnit) {
    return baseUnitName ?? baseUnitLabel ?? "—";
  }

  if (purchaseUnit.conversion_factor === 1) {
    return baseUnitName ?? purchaseUnit.name;
  }

  if (!baseUnitLabel) {
    return purchaseUnit.name;
  }

  return `${purchaseUnit.name} x ${String(purchaseUnit.conversion_factor)} ${baseUnitLabel}`;
}

function stockLabel(entry: VendorCatalogEntry): string {
  const stock = entry.product_variant?.stock;
  const baseUnit = entry.product_variant?.product?.measurement_unit;
  const baseUnitLabel = baseUnit?.abbreviation ?? baseUnit?.name;

  if (stock === null || stock === undefined || !baseUnitLabel) {
    return t("Stock unavailable");
  }

  return `${String(stock)} ${baseUnitLabel}`;
}

function stockSeverity(entry: VendorCatalogEntry): "success" | "warn" | "danger" | "secondary" {
  const stock = entry.product_variant?.stock;
  const minimumStock = entry.product_variant?.minimum_stock_level;

  if (stock === null || stock === undefined) return "secondary";
  if (stock === 0) return "danger";
  if (minimumStock && stock <= minimumStock) return "warn";

  return "success";
}

function paymentTermsLabel(paymentTerms: string | null): string | null {
  if (paymentTerms === null) return null;

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

async function searchVariants(event: { query: string }) {
  if (!props.vendorId) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select a vendor first"), life: 3000 });
    return;
  }

  if (event.query.length < 2) {
    searchResults.value = [];
    return;
  }

  searchLoading.value = true;
  try {
    const response = await fetchVendorCatalogApi(props.vendorId, event.query);
    searchResults.value = response.data.data.map((entry) => ({ ...entry, search_label: productLabel(entry) }));
  } catch {
    searchResults.value = [];
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000 });
  } finally {
    searchLoading.value = false;
  }
}

function selectEntry(event: { value: SearchOption }) {
  emit("select", event.value);
  searchResults.value = [];

  void nextTick(() => {
    selectedEntry.value = null;
    productSearch.value?.hide();
  });
}
</script>

<template>
  <div class="flex flex-col gap-2 mb-3">
    <label for="purchase-order-product-search">{{ t("Add Product") }}</label>
    <AutoComplete
      ref="productSearch"
      v-model="selectedEntry"
      input-id="purchase-order-product-search"
      :suggestions="searchResults"
      option-label="search_label"
      :placeholder="t('Search product...')"
      :empty-search-message="t('No results found')"
      :loading="searchLoading"
      :disabled="!vendorId"
      dropdown
      force-selection
      class="w-full"
      @complete="searchVariants"
      @item-select="selectEntry"
    >
      <template #header>
        <div
          class="hidden xl:grid xl:grid-cols-[minmax(0,1.5fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(6rem,0.75fr)_minmax(6.5rem,0.8fr)] xl:gap-3 xl:border-b xl:border-surface-200 xl:px-3 xl:py-2 xl:text-sm xl:font-semibold xl:text-surface-500 dark:xl:border-surface-700"
        >
          <span>{{ t("Product") }}</span>
          <span>{{ t("Brand") }}</span>
          <span>{{ t("Purchase Unit") }}</span>
          <span>{{ t("Unit Price") }}</span>
          <span>{{ t("Current Stock") }}</span>
        </div>
      </template>
      <template #option="{ option }">
        <div class="hidden w-full py-1.5 xl:block">
          <div class="grid grid-cols-[minmax(0,1.5fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(6rem,0.75fr)_minmax(6.5rem,0.8fr)] items-center gap-3">
            <div class="flex min-w-0 flex-col gap-0.5">
              <span class="truncate text-sm font-medium">{{ option.product_variant?.product?.name ?? option.product_variant?.name }}</span>
              <Badge v-if="variantLabel(option)" :value="variantLabel(option) ?? ''" severity="secondary" class="w-fit max-w-full truncate" />
            </div>
            <span class="truncate text-sm text-surface-500">{{ option.product_variant?.product?.brand?.name ?? "—" }}</span>
            <span class="truncate text-sm">{{ purchaseUnitLabel(option) }}</span>
            <span class="text-sm font-medium tabular-nums">{{ formatCurrency(String(option.price)) }}</span>
            <Tag :value="stockLabel(option)" :severity="stockSeverity(option)" class="justify-self-start text-xs" rounded />
          </div>
          <div v-if="option.minimum_order_quantity || option.lead_time_days || option.payment_terms" class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-surface-500">
            <span v-if="option.minimum_order_quantity">{{ t("Min. Order") }}: {{ option.minimum_order_quantity }}</span>
            <span v-if="option.lead_time_days">{{ t("Lead time") }}: {{ option.lead_time_days }} {{ t("days") }}</span>
            <span v-if="option.payment_terms">{{ paymentTermsLabel(option.payment_terms) }}</span>
          </div>
        </div>
        <div class="flex w-full flex-col gap-1.5 py-2 xl:hidden">
          <div class="flex items-start justify-between gap-3">
            <div class="flex flex-col gap-0.5 min-w-0 flex-1">
              <span class="font-medium text-sm truncate">{{ option.product_variant?.product?.name ?? option.product_variant?.name }}</span>
              <Badge v-if="variantLabel(option)" :value="variantLabel(option) ?? ''" severity="secondary" class="w-fit max-w-full truncate" />
              <span v-if="option.product_variant?.product?.brand?.name" class="text-xs text-surface-500 truncate">{{ option.product_variant.product.brand.name }}</span>
              <span class="text-xs text-surface-500">{{ purchaseUnitLabel(option) }}</span>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <span class="font-medium tabular-nums">{{ formatCurrency(String(option.price)) }}</span>
              <Tag :value="stockLabel(option)" :severity="stockSeverity(option)" class="text-xs" />
            </div>
          </div>
          <div v-if="option.minimum_order_quantity || option.lead_time_days || option.payment_terms" class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-surface-500">
            <span v-if="option.minimum_order_quantity">{{ t("Min. Order") }}: {{ option.minimum_order_quantity }}</span>
            <span v-if="option.lead_time_days">{{ t("Lead time") }}: {{ option.lead_time_days }} {{ t("days") }}</span>
            <span v-if="option.payment_terms">{{ paymentTermsLabel(option.payment_terms) }}</span>
          </div>
        </div>
      </template>
    </AutoComplete>
    <small v-if="!vendorId" class="text-surface-500">{{ t("Select a vendor first to add products") }}</small>
  </div>
</template>
