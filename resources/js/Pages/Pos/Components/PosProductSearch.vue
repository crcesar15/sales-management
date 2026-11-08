<script setup lang="ts">
import { Button, IconField, InputIcon, InputText, Message, SelectButton, ToggleSwitch, useToast } from "primevue";
import { computed, nextTick, onScopeDispose, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { usePosProductView, type PosProductView } from "@composables/usePosProductView";
import { usePosUnitStock } from "@composables/usePosUnitStock";
import { useSalesOrderClient } from "@composables/useSalesOrderClient";
import { isScannerSupported } from "@composables/useBarcodeScanner";
import ProductInfoPopover from "./ProductInfoPopover.vue";
import PosProductResults from "./PosProductResults.vue";
import PosScannerDialog from "./PosScannerDialog.vue";
import type { PosAddItemOptions, PosCartItem, PosProductInfo, PosSellableUnit } from "@/Types/pos";
import type { VariantSearchResult } from "@/Types/sales-order-types";

type BarcodeCommitStatus = "added" | "needs-selection" | "no-match" | "stock-unavailable";
type ProductSearchStatus = BarcodeCommitStatus | "cancelled" | "error" | null;

interface ProductSearchOptions {
  autoAddBarcode?: boolean;
  forceBarcodeCommit?: boolean;
  canCommit?: () => boolean;
  addOptions?: PosAddItemOptions;
}

const props = withDefaults(defineProps<{
  storeId: number | null;
  cartItems: PosCartItem[];
  getRemainingBase: (variantId: number) => number | null;
  inputId?: string;
  focusOnClear?: boolean;
  toastGroup?: string | null;
}>(), { toastGroup: "pos" });

const emit = defineEmits<{
  (e: "add", item: PosCartItem, options?: PosAddItemOptions): void;
}>();

const barcodeMode = defineModel<boolean>("barcodeMode", { default: false });
const { t } = useI18n();
const toast = useToast();
const { searchVariantsApi } = useSalesOrderClient();
const { productView, setProductView } = usePosProductView();
const { canAdd } = usePosUnitStock(remainingBase);

function remainingBase(variantId: number): number | null {
  const live = props.getRemainingBase(variantId);
  if (live !== null) return live;
  const snapshot = results.value.find((variant) => variant.id === variantId)?.stock ?? null;
  if (snapshot === null) return null;
  const allocated = props.cartItems.reduce((total, item) => item.product_variant_id === variantId
    ? total + item.quantity * (item.conversion_factor > 0 ? item.conversion_factor : 1)
    : total, 0);
  return snapshot - allocated;
}

const viewOptions = computed<Array<{ value: PosProductView; icon: string; label: string }>>(() => [
  { value: "grid", icon: "fa fa-table-cells-large", label: t("Grid view") },
  { value: "list", icon: "fa fa-list", label: t("List view") },
]);

const query = ref("");
const results = ref<VariantSearchResult[]>([]);
const loading = ref(false);
const failed = ref(false);
const searched = ref(false);
const scannerSupported = isScannerSupported();
const scannerVisible = ref(false);
let requestId = 0;
let barcodeModeVersion = 0;
let scannerSessionId = 0;
let activeSearchController: AbortController | null = null;

const units = computed<PosSellableUnit[]>(() =>
  results.value.flatMap((variant) => {
    const baseUnitName = variant.product?.measurement_unit?.name ?? t("Unit");
    const basePrice = Number(variant.price) || 0;
    const baseUnit: PosSellableUnit = {
      key: `${variant.id}:base`,
      variant,
      saleUnitId: null,
      saleUnitName: baseUnitName,
      conversionFactor: 1,
      unitPrice: basePrice,
    };

    return [
      baseUnit,
      ...(variant.sale_units ?? []).map((saleUnit) => ({
        key: `${variant.id}:${saleUnit.id}`,
        variant,
        saleUnitId: saleUnit.id,
        saleUnitName: saleUnit.name,
        conversionFactor: saleUnit.conversion_factor > 0 ? saleUnit.conversion_factor : 1,
        unitPrice: Number(saleUnit.price) || 0,
      })),
    ];
  }),
);

const shouldSearch = computed(() => query.value.trim().length >= 2 && props.storeId !== null);
const showPrompt = computed(() => !searched.value && !loading.value && !failed.value);
const showEmpty = computed(() => searched.value && !loading.value && !failed.value && units.value.length === 0);

const infoPopover = ref();
const infoUnitKey = ref<string | null>(null);
const infoData = computed<PosProductInfo | null>(() => {
  const unit = units.value.find((entry) => entry.key === infoUnitKey.value);
  if (unit === undefined) return null;

  return {
    product_name: unit.variant.product?.name ?? "—",
    brand_name: unit.variant.product?.brand?.name ?? null,
    unit_price: unit.unitPrice,
    base_unit_name: unit.variant.product?.measurement_unit?.name ?? null,
    sale_unit_name: unit.saleUnitId !== null ? unit.saleUnitName : null,
    conversion_factor: unit.conversionFactor,
  };
});

function toggleInfo(event: Event, unit: PosSellableUnit): void {
  infoUnitKey.value = unit.key;
  infoPopover.value?.toggle(event);
}

function cancelSearch(): void {
  activeSearchController?.abort();
  activeSearchController = null;
  requestId++;
  loading.value = false;
}

async function searchProducts(filterValue: string, options: ProductSearchOptions = {}): Promise<ProductSearchStatus> {
  const filter = filterValue.trim();
  const currentBarcodeModeVersion = barcodeModeVersion;
  if (filter.length < 2 || props.storeId === null) {
    resetResults();
    return null;
  }

  cancelSearch();
  const controller = new AbortController();
  activeSearchController = controller;
  loading.value = true;
  failed.value = false;
  searched.value = true;
  const currentRequest = ++requestId;

  try {
    const response = await searchVariantsApi(filter, props.storeId, controller.signal, { withThumbnail: true });
    if (controller.signal.aborted || currentRequest !== requestId) return "cancelled";

    const data = response.data?.data ?? [];
    results.value = Array.isArray(data) ? (data as VariantSearchResult[]) : [];

    const shouldCommitBarcode =
      options.autoAddBarcode && (options.forceBarcodeCommit || (barcodeMode.value && currentBarcodeModeVersion === barcodeModeVersion));
    if (!shouldCommitBarcode) return null;
    if (options.canCommit !== undefined && !options.canCommit()) return "cancelled";

    return handleBarcodeCommit(filter, options.addOptions);
  } catch {
    if (controller.signal.aborted || currentRequest !== requestId) return "cancelled";

    results.value = [];
    failed.value = true;
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000, group: props.toastGroup ?? undefined });
    return "error";
  } finally {
    if (currentRequest === requestId) {
      loading.value = false;
      activeSearchController = null;
    }
  }
}

function retrySearch(): void {
  void searchProducts(query.value);
}

function onSearchKeydown(event: KeyboardEvent): void {
  if (event.isComposing || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) return;
  if (!shouldSearch.value) return;

  event.preventDefault();

  submitSearch();
}

function submitSearch(): void {
  if (!shouldSearch.value) return;

  void searchProducts(query.value, { autoAddBarcode: barcodeMode.value });
}

function handleBarcodeCommit(barcode: string, options?: PosAddItemOptions): BarcodeCommitStatus {
  const barcodeMatches = results.value.filter((variant) => variant.barcode === barcode);
  if (barcodeMatches.length === 0) return "no-match";
  if (barcodeMatches.length > 1) return "needs-selection";

  const [variant] = barcodeMatches;
  const matchingUnits = units.value.filter((unit) => unit.variant.id === variant.id);
  if (matchingUnits.length !== 1) return "needs-selection";

  const [unit] = matchingUnits;

  if (!canAdd(unit)) {
    toast.add({
      severity: "warn",
      summary: t("Stock limit reached"),
      detail: t("No more stock is available for this product"),
      life: 3000,
      group: props.toastGroup ?? undefined,
    });
    return "stock-unavailable";
  }

  addUnit(unit, options);
  return "added";
}

function notifyScanNotFound(): void {
  toast.add({
    severity: "warn",
    summary: t("Product not found"),
    detail: t("No product matches the scanned code"),
    life: 3000,
    group: props.toastGroup ?? undefined,
  });
}

function openScanner(): void {
  if (props.storeId === null) return;

  scannerSessionId++;
  scannerVisible.value = true;
}

function isScannerSessionActive(sessionId: number): boolean {
  return scannerVisible.value && sessionId === scannerSessionId;
}

async function handleScan(rawCode: string): Promise<void> {
  const sessionId = scannerSessionId;
  if (!isScannerSessionActive(sessionId)) return;

  const code = rawCode.trim();
  if (props.storeId === null || code.length < 2) {
    notifyScanNotFound();
    return;
  }

  setScannerQuery(code);
  const status = await searchProducts(code, {
    autoAddBarcode: true,
    forceBarcodeCommit: true,
    canCommit: () => isScannerSessionActive(sessionId),
    addOptions: { keepProductPickerOpen: true },
  });

  if (!isScannerSessionActive(sessionId)) return;

  if (status === "no-match") notifyScanNotFound();
  else if (status === "needs-selection") scannerVisible.value = false;
}

function resetResults(): void {
  cancelSearch();
  results.value = [];
  failed.value = false;
  searched.value = false;
}

function updateQuery(value: string | undefined): void {
  query.value = value ?? "";
  resetResults();
}

function setScannerQuery(value: string): void {
  query.value = value;
  resetResults();
}

function clearSearch(focus = props.focusOnClear !== false): void {
  query.value = "";
  resetResults();
  if (focus) focusSearch();
}

function focusSearch(): void {
  void nextTick(() => document.getElementById(props.inputId ?? "pos-product-search")?.focus());
}

function resetSearch(): void {
  scannerVisible.value = false;
  clearSearch();
}

function addUnit(unit: PosSellableUnit, options?: PosAddItemOptions): void {
  if (!canAdd(unit)) return;

  const variant = unit.variant;
  emit(
    "add",
    {
      id: crypto.randomUUID(),
      product_variant_id: variant.id,
      product_name: variant.product?.name ?? "—",
      brand_name: variant.product?.brand?.name ?? null,
      base_unit_name: variant.product?.measurement_unit?.name ?? null,
      variant_identity: variant.option_values,
      variant_label: variant.option_values ?? unit.saleUnitName,
      identifier: variant.identifier ?? null,
      barcode: variant.barcode ?? null,
      has_multiple_sale_units: (variant.sale_units?.length ?? 0) > 0,
      sale_unit_id: unit.saleUnitId,
      quantity: 1,
      unit_price: unit.unitPrice,
      original_unit_price: unit.unitPrice,
      line_discount: 0,
      conversion_factor: unit.conversionFactor,
      line_total: unit.unitPrice,
      stock: variant.stock ?? null,
      minimum_stock_level: variant.minimum_stock_level ?? null,
      sale_units: variant.sale_units ?? [],
      sale_unit:
        unit.saleUnitId === null ? null : { id: unit.saleUnitId, name: unit.saleUnitName, conversion_factor: unit.conversionFactor },
    },
    options,
  );

  clearSearch(!options?.keepProductPickerOpen && props.focusOnClear !== false);
}

watch(barcodeMode, () => {
  barcodeModeVersion++;
});

watch(() => props.storeId, resetSearch);

watch(
  scannerVisible,
  (isVisible) => {
    if (!isVisible) {
      scannerSessionId++;
      cancelSearch();
    }
  },
  { flush: "sync" },
);

onScopeDispose(() => {
  scannerSessionId++;
  cancelSearch();
});

defineExpose({ focusSearch, resetSearch });
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col" :aria-label="t('Products')">
    <div class="border-b border-surface-200 p-4 dark:border-surface-700">
      <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
        <label :for="inputId ?? 'pos-product-search'" class="text-lg font-semibold">{{ t("Find products") }}</label>
        <div class="flex w-full justify-between">
          <SelectButton
            :model-value="productView"
            :options="viewOptions"
            option-value="value"
            option-label="label"
            :allow-empty="false"
            :aria-label="t('Results view')"
            @update:model-value="setProductView"
          >
            <template #option="{ option }">
              <i v-tooltip.bottom="option.label" :class="option.icon" aria-hidden="true" />
              <span class="sr-only">{{ option.label }}</span>
            </template>
          </SelectButton>
          <div class="flex items-center gap-2">
            <label :for="`${inputId ?? 'pos-product-search'}-barcode-mode`" class="text-sm font-medium">{{ t("Barcode mode") }}</label>
            <ToggleSwitch
              :input-id="`${inputId ?? 'pos-product-search'}-barcode-mode`"
              :aria-describedby="`${inputId ?? 'pos-product-search'}-barcode-mode-help`"
              v-model="barcodeMode"
            />
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <IconField class="min-w-0 flex-1">
          <InputIcon class="fa fa-search" />
          <InputIcon
            v-if="scannerSupported"
            class="fa fa-barcode left-[2.25rem] cursor-pointer"
            :class="{ 'pointer-events-none opacity-50': storeId === null }"
            role="button"
            tabindex="0"
            :aria-label="t('Scan barcode with camera')"
            :title="t('Scan barcode with camera')"
            @click="openScanner"
            @keydown.enter.prevent="openScanner"
          />
          <InputText
            :id="inputId ?? 'pos-product-search'"
            :model-value="query"
            :placeholder="t('Search by product, identifier or barcode')"
            :disabled="storeId === null"
            autocomplete="off"
            enterkeyhint="search"
            size="large"
            class="w-full"
            :style="scannerSupported ? { paddingLeft: '4rem' } : undefined"
            :aria-busy="loading"
            @update:model-value="updateQuery"
            @keydown.enter="onSearchKeydown"
          />
          <InputIcon
            v-if="query"
            class="fa fa-xmark cursor-pointer"
            role="button"
            tabindex="0"
            :aria-label="t('Clear search')"
            @click="clearSearch"
            @keydown.enter.prevent="clearSearch"
          />
        </IconField>
        <Button
          type="button"
          icon="fa fa-search"
          :aria-label="t('Search')"
          :disabled="!shouldSearch || loading"
          :loading="loading"
          raised
          class="min-h-11 shrink-0"
          @click="submitSearch"
        />
      </div>
      <small :id="`${inputId ?? 'pos-product-search'}-barcode-mode-help`" class="mt-2 block text-surface-500 dark:text-surface-400">
        {{
          barcodeMode
            ? t("Exact barcode matches are added automatically. Products with multiple sale units require selection.")
            : t("Search by name, identifier or barcode, then select a product.")
        }}
      </small>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto" aria-live="polite">
      <div
        v-if="showPrompt"
        class="flex min-h-64 flex-col items-center justify-center px-6 text-center text-surface-500 dark:text-surface-400"
      >
        <i class="fa fa-magnifying-glass mb-3 text-3xl" aria-hidden="true" />
        <p class="m-0 font-medium">{{ t("Search the catalog") }}</p>
        <small>{{ t("Products matching your search will appear here") }}</small>
      </div>

      <div
        v-else-if="loading"
        class="p-4"
        :class="
          productView === 'grid'
            ? 'grid grid-cols-[repeat(auto-fill,minmax(max(9.5rem,calc((100%_-_24px)/3)),1fr))] gap-3'
            : 'flex flex-col gap-3'
        "
        aria-label="Loading"
      >
        <template v-if="productView === 'grid'">
          <div
            v-for="index in 5"
            :key="index"
            class="flex flex-col overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
          >
            <div class="aspect-square animate-pulse bg-surface-100 dark:bg-surface-800" />
            <div class="p-3">
              <div class="h-24 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
            </div>
          </div>
        </template>
        <template v-else>
          <div v-for="index in 5" :key="index" class="h-20 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
        </template>
      </div>

      <Message v-else-if="failed" severity="error" :closable="false" class="m-4">
        <div class="flex items-center justify-between gap-3">
          <span>{{ t("Product search failed") }}</span>
          <Button :label="t('Retry')" size="small" severity="danger" outlined @click="retrySearch" />
        </div>
      </Message>

      <div
        v-else-if="showEmpty"
        class="flex min-h-64 flex-col items-center justify-center px-6 text-center text-surface-500 dark:text-surface-400"
      >
        <i class="fa fa-box-open mb-3 text-3xl" aria-hidden="true" />
        <p class="m-0 font-medium">{{ t("No products found") }}</p>
        <small>{{ t("Try another product name, identifier or barcode") }}</small>
      </div>

      <PosProductResults
        v-else
        :units="units"
        :view="productView"
        :get-remaining-base="remainingBase"
        @add="addUnit"
        @info="toggleInfo"
      />
    </div>

    <ProductInfoPopover ref="infoPopover" :info="infoData" />
    <PosScannerDialog v-model:visible="scannerVisible" :process-code="handleScan" />
  </section>
</template>
