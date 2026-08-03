<script setup lang="ts">
import { Tag, Badge, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useSalesOrderClient } from "@/Composables/useSalesOrderClient";
import type { SalesOrderLineItemForm, VariantSearchResult } from "@/Types/sales-order-types";

type LineItem = SalesOrderLineItemForm;

/**
 * A single sellable unit row, flattened from a VariantSearchResult.
 * One variant with N sale units becomes 1 + N rows (base unit always first).
 */
interface UnitRow {
  /** Stable key: `${variantId}:${unitId ?? "base"}` — matches addedKeys */
  key: string;
  variantId: number;
  /** Sale unit id, or null for the base measurement unit */
  unitId: number | null;
  unitName: string;
  conversionFactor: number;
  /** Unit-specific price */
  price: number;
  /** Base-stock snapshot from search time (before any in-order allocation) */
  baseStock: number | null;
  /** Per-variant minimum stock level, in BASE units */
  minStockBase: number | null;
  isBase: boolean;
  /** Reference to the source variant for context columns (product, brand, badges) */
  variant: VariantSearchResult;
}

const props = defineProps<{
  addedKeys: Set<string>;
  /**
   * Live remaining base stock for a variant, after subtracting base units
   * already allocated across all line items in the order. Returns null when
   * the variant is not in the order (use the static snapshot).
   */
  getRemainingBase?: (variantId: number) => number | null;
  /**
   * Store to scope stock to. When null, the picker is disabled (no store
   * chosen yet) and searches are not fired.
   */
  storeId?: number | null;
}>();

const emit = defineEmits<{
  (e: "add", item: LineItem): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const toast = useToast();
const { searchVariantsApi } = useSalesOrderClient();

function buildUnitRows(variant: VariantSearchResult): UnitRow[] {
  const baseStock = variant.stock ?? null;
  const minStock = variant.minimum_stock_level ?? null;
  const baseUnitName = variant.product?.measurement_unit?.name ?? t("Unit");
  const basePrice = variant.price ? parseFloat(String(variant.price)) : 0;

  const rows: UnitRow[] = [];

  rows.push({
    key: `${variant.id}:base`,
    variantId: variant.id,
    unitId: null,
    unitName: baseUnitName,
    conversionFactor: 1,
    price: basePrice,
    baseStock,
    minStockBase: minStock,
    isBase: true,
    variant,
  });

  const saleUnits = variant.sale_units ?? [];
  for (const unit of saleUnits) {
    const cf = unit.conversion_factor > 0 ? unit.conversion_factor : 1;
    rows.push({
      key: `${variant.id}:${unit.id}`,
      variantId: variant.id,
      unitId: unit.id,
      unitName: unit.name,
      conversionFactor: cf,
      price: parseFloat(String(unit.price)),
      baseStock,
      minStockBase: minStock,
      isBase: false,
      variant,
    });
  }

  return rows;
}

/** Flatten all variants into one row per sellable unit, preserving search order. */
const flatRows = computed<UnitRow[]>(() => searchResults.value.flatMap(buildUnitRows));

/** Remaining base stock after all draft allocations, falling back to the search snapshot. */
function remainingBase(row: UnitRow): number | null {
  return props.getRemainingBase?.(row.variantId) ?? row.baseStock;
}

/** Live remaining stock in this row's sale unit. */
function remainingInUnit(row: UnitRow): number | null {
  const base = remainingBase(row);
  if (base === null) return null;
  const cf = row.conversionFactor > 0 ? row.conversionFactor : 1;
  return Math.floor(base / cf);
}

/** Sale-unit status for the immediate selection guard. */
function getStockSeverity(row: UnitRow): "success" | "warn" | "danger" {
  const remaining = remainingInUnit(row);
  if (remaining === null) return "success";
  if (remaining <= 0) return "danger";
  if (row.minStockBase !== null && row.minStockBase > 0) {
    const minInUnit = Math.ceil(row.minStockBase / row.conversionFactor);
    if (remaining <= minInUnit) return "warn";
  }
  return "success";
}

function getStockLabel(row: UnitRow): string {
  const remaining = remainingInUnit(row);
  if (remaining === null) return "—";
  if (remaining <= 0) return t("Out of stock");
  return `${t("Remaining")}: ${String(remaining)}`;
}

function isRowDisabled(row: UnitRow): boolean {
  return props.addedKeys.has(row.key) || getStockSeverity(row) === "danger";
}

const query = ref("");
const searchResults = ref<VariantSearchResult[]>([]);
const searchLoading = ref(false);
const searchFailed = ref(false);
const isOpen = ref(false);
const activeIndex = ref(-1);
const additionAnnouncement = ref("");

const rootRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;

function findNextAvailableIndex(startIndex: number, direction: 1 | -1): number {
  const rows = flatRows.value;
  for (let offset = 1; offset <= rows.length; offset++) {
    const index =
      startIndex === -1
        ? direction === 1
          ? offset - 1
          : rows.length - offset
        : (startIndex + direction * offset + rows.length) % rows.length;
    const row = rows[index];
    if (row && !isRowDisabled(row)) return index;
  }

  return -1;
}

function moveActiveOption(direction: 1 | -1) {
  activeIndex.value = findNextAvailableIndex(activeIndex.value, direction);
  if (activeIndex.value >= 0) scrollActiveIntoView();
}

async function performSearch() {
  const q = query.value.trim();
  if (q.length < 2 || props.storeId == null) {
    searchResults.value = [];
    searchLoading.value = false;
    return;
  }
  searchLoading.value = true;
  searchFailed.value = false;
  const reqId = ++currentRequestId;
  try {
    const response = await searchVariantsApi(q, props.storeId);
    if (reqId !== currentRequestId) return;
    const data = response.data?.data ?? [];
    searchResults.value = Array.isArray(data) ? (data as VariantSearchResult[]) : [];
    activeIndex.value = findNextAvailableIndex(-1, 1);
  } catch {
    if (reqId !== currentRequestId) return;
    searchResults.value = [];
    searchFailed.value = true;
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000 });
  } finally {
    if (reqId === currentRequestId) searchLoading.value = false;
  }
}

watch(query, () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  // Invalidate an in-flight response and clear its options before waiting to search again.
  currentRequestId++;
  searchResults.value = [];
  searchFailed.value = false;
  activeIndex.value = -1;
  debounceTimer = setTimeout(performSearch, 300);
});

function openPanel() {
  isOpen.value = true;
}

function closePanel() {
  isOpen.value = false;
  activeIndex.value = -1;
}

function onInputFocus() {
  if (query.value.trim().length >= 2 && searchResults.value.length > 0) {
    openPanel();
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === "ArrowDown") {
    event.preventDefault();
    if (flatRows.value.length > 0) {
      openPanel();
      moveActiveOption(1);
    }
  } else if (event.key === "ArrowUp") {
    event.preventDefault();
    if (flatRows.value.length > 0) {
      openPanel();
      moveActiveOption(-1);
    }
  } else if (event.key === "Escape") {
    if (isOpen.value) {
      event.preventDefault();
      closePanel();
    }
  } else if (event.key === "Enter") {
    if (isOpen.value && activeIndex.value >= 0 && activeIndex.value < flatRows.value.length) {
      event.preventDefault();
      const row = flatRows.value[activeIndex.value];
      if (row && !isRowDisabled(row)) {
        addUnitRow(row);
      }
    }
  }
}

function scrollActiveIntoView() {
  nextTick(() => {
    const list = listRef.value;
    if (!list) return;
    const active = list.querySelector<HTMLElement>('[data-active="true"]');
    if (active) {
      active.scrollIntoView({ block: "nearest" });
    }
  });
}

function onOutsideClick(event: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(event.target as Node)) {
    closePanel();
  }
}

onMounted(() => {
  document.addEventListener("mousedown", onOutsideClick);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onOutsideClick);
  if (debounceTimer) clearTimeout(debounceTimer);
});

function addUnitRow(row: UnitRow) {
  const variant = row.variant;
  const productName = variant.product?.name ?? "—";
  const variantIdentity = variant.option_values;
  // Keep variant identity and sale unit separate so price edits remain traceable
  // to the exact sellable unit selected by the operator.
  const displayLabel = variantIdentity ?? row.unitName;

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: productName,
    brand_name: variant.product?.brand?.name ?? null,
    base_unit_name: variant.product?.measurement_unit?.name ?? null,
    variant_identity: variantIdentity,
    variant_label: displayLabel,
    sale_unit_id: row.unitId,
    quantity: 1,
    unit_price: row.price,
    original_unit_price: row.price,
    conversion_factor: row.conversionFactor,
    line_total: row.price * 1,
    stock: row.baseStock,
    minimum_stock_level: row.minStockBase,
    sale_units: variant.sale_units ?? [],
    sale_unit: row.isBase ? null : { id: row.unitId as number, name: row.unitName, conversion_factor: row.conversionFactor },
  };

  emit("add", newItem);
  additionAnnouncement.value = `${t("Product added")}: ${productName}`;
  query.value = "";
  searchResults.value = [];
  closePanel();
  nextTick(() => inputRef.value?.focus());
}

const inputDisabled = computed(() => props.storeId == null);
const inputPlaceholder = computed(() => (props.storeId == null ? t("Select a store first") : t("Search product...")));

const showEmpty = computed(
  () => isOpen.value && !searchLoading.value && !searchFailed.value && query.value.trim().length >= 2 && searchResults.value.length === 0,
);
const isPanelVisible = computed(() => isOpen.value && !searchLoading.value && (flatRows.value.length > 0 || showEmpty.value));
const searchAnnouncement = computed(() => {
  if (searchLoading.value) return t("Searching products");
  if (searchFailed.value) return t("Product search failed");
  if (query.value.trim().length < 2) return "";

  return t("Product options available", flatRows.value.length);
});
</script>

<template>
  <div ref="rootRef" class="flex flex-col gap-2 mb-3">
    <span class="sr-only" role="status" aria-live="polite">{{ additionAnnouncement }}</span>
    <span class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ searchAnnouncement }}</span>
    <div class="so-combobox">
      <div class="so-input-wrap">
        <i class="fa fa-search so-search-icon" aria-hidden="true" />
        <input
          id="so-product-search"
          ref="inputRef"
          v-model="query"
          type="text"
          autocomplete="off"
          role="combobox"
          :disabled="inputDisabled"
          :placeholder="inputPlaceholder"
          :aria-expanded="isPanelVisible"
          :aria-busy="searchLoading"
          aria-autocomplete="list"
          :aria-controls="isPanelVisible ? 'so-product-results' : undefined"
          :aria-activedescendant="isPanelVisible && activeIndex >= 0 ? `so-opt-${activeIndex}` : undefined"
          class="so-input"
          @focus="onInputFocus"
          @input="openPanel"
          @keydown="onKeydown"
        />
        <i v-if="searchLoading" class="fa fa-spinner fa-spin so-spinner" aria-hidden="true" />
      </div>

      <Transition name="so-panel">
        <div
          v-if="isPanelVisible && (flatRows.length > 0 || showEmpty)"
          id="so-product-results"
          ref="listRef"
          class="so-panel"
          role="listbox"
          :aria-label="t('Product search results')"
        >
          <div class="so-panel-header">
            <span class="so-col so-col-product">{{ t("Product") }}</span>
            <span class="so-col so-col-brand">{{ t("Brand") }}</span>
            <span class="so-col so-col-unit">{{ t("Sale Unit") }}</span>
            <span class="so-col so-col-price">{{ t("Price") }}</span>
            <span class="so-col so-col-stock">{{ t("Remaining") }}</span>
          </div>

          <div
            v-for="(row, rIndex) in flatRows"
            :id="`so-opt-${rIndex}`"
            :key="row.key"
            :class="[
              'so-option',
              isRowDisabled(row) ? 'so-option-disabled' : '',
              getStockSeverity(row) === 'danger' ? 'so-option-danger' : '',
              getStockSeverity(row) === 'warn' ? 'so-option-warn' : '',
              addedKeys.has(row.key) ? 'so-option-added' : '',
            ]"
            role="option"
            :aria-selected="rIndex === activeIndex"
            :aria-disabled="isRowDisabled(row)"
            :data-active="rIndex === activeIndex"
            @mouseenter="!isRowDisabled(row) && (activeIndex = rIndex)"
            @click="!isRowDisabled(row) && addUnitRow(row)"
          >
            <!-- Desktop: grid row -->
            <div class="so-row so-row-desktop">
              <div class="so-col so-col-product so-product-cell">
                <span class="so-product-name !text-[1.125rem] !font-semibold" >{{ row.variant.product?.name ?? "—" }}</span>
                <div v-if="row.variant.option_values" class="flex flex-wrap gap-1">
                  <Badge v-if="row.variant.option_values" :value="row.variant.option_values" severity="primary" class="!font-semibold" />
                </div>
              </div>
              <div class="so-col so-col-brand so-brand-cell !font-bold">{{ row.variant.product?.brand?.name ?? "—" }}</div>
              <div class="so-col so-col-unit so-unit-cell">
                <span class="font-medium">{{ row.unitName }}</span>
                <span v-if="row.conversionFactor !== 1" class="so-unit-factor">×{{ row.conversionFactor }}</span>
              </div>
              <div class="so-col so-col-price so-price-cell">{{ formatCurrency(String(row.price)) }}</div>
              <div class="so-col so-col-stock so-stock-cell">
                <Tag :value="getStockLabel(row)" :severity="getStockSeverity(row)" class="!text-[14px]" rounded />
                <span
                  v-if="addedKeys.has(row.key)"
                  v-tooltip.top="t('Already added')"
                  class="so-added-status"
                  role="img"
                  :aria-label="t('Already added')"
                >
                  <i class="fa fa-check" aria-hidden="true" />
                </span>
              </div>
            </div>

            <!-- Mobile: stacked card -->
            <div class="so-row so-row-mobile">
              <div class="so-mobile-top">
                <div class="so-mobile-identity">
                  <span class="so-product-name">{{ row.variant.product?.name ?? "—" }}</span>
                  <Badge v-if="row.variant.option_values" :value="row.variant.option_values" severity="primary" class="!font-semibold !text-[14px]" />
                </div>
                <div class="so-mobile-right">
                  <span class="so-price-cell">{{ formatCurrency(String(row.price)) }}</span>
                </div>
              </div>
              <span v-if="row.variant.product?.brand?.name" class="so-mobile-brand"><i class="fa fa-tag mr-2"></i>{{ row.variant.product.brand.name }}</span>
              <div class="so-mobile-bottom">
                <div class="so-unit-cell">
                  <span class="font-medium"><i class="fa fa-weight-hanging mr-2"></i>{{ row.unitName }}</span>
                  <span v-if="row.conversionFactor !== 1" class="so-unit-factor">×{{ row.conversionFactor }}</span>
                </div>
                <Tag :value="getStockLabel(row)" :severity="getStockSeverity(row)" class="!text-[14px]" rounded />
                <span
                  v-if="addedKeys.has(row.key)"
                  v-tooltip.top="t('Already added')"
                  class="so-added-status"
                  role="img"
                  :aria-label="t('Already added')"
                >
                  <i class="fa fa-check" aria-hidden="true" />
                </span>
              </div>
            </div>
          </div>

          <div v-if="showEmpty" class="so-empty">
            <i class="fa fa-magnifying-glass" aria-hidden="true" />
            <span>{{ t("No results found") }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style>
/* Global (non-scoped): bespoke so-* class names avoid collisions. */

.so-combobox {
  position: relative;
  width: 100%;
}

.so-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.so-search-icon {
  position: absolute;
  left: 0.75rem;
  color: var(--p-surface-600, #475569);
  font-size: 0.875rem;
  pointer-events: none;
  z-index: 1;
}

.so-input {
  width: 100%;
  min-height: 44px;
  padding: 0.625rem 2.25rem;
  border: 1px solid var(--p-inputtext-border-color, #cbd5e1);
  border-radius: 6px;
  background: var(--p-inputtext-background, #ffffff);
  color: var(--p-inputtext-color, #1e293b);
  font-family: inherit;
  font-size: 1rem;
  line-height: 1.5;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.so-input::placeholder {
  color: var(--p-surface-600, #475569);
}

.so-input:focus {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.so-input:disabled {
  background: var(--p-surface-100, #f1f5f9);
  color: var(--p-surface-700, #334155);
}

.so-spinner {
  position: absolute;
  right: 0.75rem;
  color: var(--p-surface-500, #64748b);
  font-size: 0.875rem;
}

.so-panel {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 50;
  background: var(--p-overlay-background, #ffffff);
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 6px;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -2px rgba(0, 0, 0, 0.1);
  max-height: 420px;
  overflow-y: auto;
  overflow-x: hidden;
}

.so-panel-header {
  display: none;
  grid-template-columns: 5fr 2fr 2fr 3fr;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--p-surface-700, #334155);
  border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
  position: sticky;
  top: 0;
  background: var(--p-overlay-background, #ffffff);
  z-index: 1;
}

.so-option {
  border-bottom: 1px solid var(--p-surface-100, #f1f5f9);
  cursor: pointer;
  transition:
    background-color 0.12s ease,
    box-shadow 0.12s ease;
}

.so-option:last-child {
  border-bottom: none;
}

.so-option[data-active="true"]:not(.so-option-disabled) {
  background: var(--p-surface-100, #f1f5f9);
  box-shadow: inset 0 0 0 1px var(--p-primary-color, #00539b);
}

.so-option[data-active="true"].so-option-disabled {
  background: var(--p-surface-50, #f8fafc);
}

.so-option:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.so-option-disabled {
  cursor: default;
  background: var(--p-surface-50, #f8fafc);
}

.so-option-added {
  cursor: default;
  background: var(--p-surface-50, #f8fafc);
}

.so-row {
  min-height: 80px;
  padding: 0.5rem 0.75rem;
}

.so-row-desktop {
  display: none;
}

.so-row-mobile {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.so-col {
  min-width: 0;
}

.so-product-cell {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.so-product-name {
  font-weight: 600;
  font-size: 1rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.so-brand-inline {
  color: var(--p-surface-700, #334155);
  font-size: 1rem;
}

.so-brand-cell {
  color: var(--p-surface-700, #334155);
  font-size: 1rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.so-unit-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 1rem;
}

.so-unit-factor {
  color: var(--p-surface-700, #334155);
  font-size: 1rem;
  font-weight: 400;
}

.so-price-cell {
  font-weight: 600;
  font-size: 1rem;
  font-variant-numeric: tabular-nums;
}

.so-stock-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.so-mobile-top {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: flex-start;
  gap: 0.5rem;
}

.so-mobile-identity {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.so-mobile-identity .so-product-name {
  flex: 0 1 auto;
  max-width: 100%;
  min-width: 0;
}

.so-mobile-identity .p-badge {
  flex: none;
}

.so-mobile-brand {
  color: var(--p-surface-700, #334155);
  font-size: 1.125rem;
}

.so-mobile-right {
  display: flex;
  flex-shrink: 0;
}

.so-mobile-bottom {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.so-mobile-bottom .so-unit-cell {
  margin-right: auto;
}

.so-added-status {
  display: inline-flex;
  align-items: center;
  color: var(--p-green-700, #15803d);
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
}

.so-empty {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.75rem 1rem;
  color: var(--p-surface-600, #475569);
  font-size: 1.125rem;
}

.so-empty i {
  font-size: 1.5rem;
}

/* The application compresses rem units on mobile; preserve an arm's-length search control. */
@media (max-width: 768px) {
  .so-input {
    min-height: 48px;
    padding: 10px 44px;
    font-size: 16px;
  }

  .so-search-icon,
  .so-spinner {
    font-size: 16px;
  }

  .so-search-icon {
    left: 14px;
  }

  .so-spinner {
    right: 14px;
  }

  .so-product-name {
    font-size: 1.5rem;
    font-weight: bold;
  }

  .so-unit-cell,
  .so-unit-factor {
    font-size: 1.125rem;
  }

  .so-brand-cell {
    font-size: 1.125rem;
  }

  .so-price-cell {
    font-size: 1.125rem;
  }

  .so-stock-cell {
    font-size: 1.125rem;
  }
}

/* Panel transition */
.so-panel-enter-active,
.so-panel-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}

.so-panel-enter-from,
.so-panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Desktop layout: grid row */
@media (min-width: 1024px) {
  .so-row {
    min-height: 60px;
  }

  .so-panel-header {
    display: grid;
    grid-template-columns: 4fr 2fr 2fr 2fr 2fr;
  }

  .so-row-desktop {
    display: grid;
    grid-template-columns: 4fr 2fr 2fr 2fr 2fr;
    gap: 0.5rem;
    align-items: center;
  }

  .so-row-mobile {
    display: none;
  }

  .so-col-product {
    align-self: center;
  }

  .so-col-brand {
    align-self: center;
  }

  .so-col-unit {
    align-self: center;
  }

  .so-col-price {
    align-self: center;
  }

  .so-col-stock {
    align-self: center;
  }
}

/* Out-of-stock / low-stock border cues (full border, never side-stripe) */
.so-option-danger:not(.so-option-added) {
  border-bottom-color: var(--p-red-300, #fca5a5);
}

.so-option-warn:not(.so-option-added) {
  border-bottom-color: var(--p-amber-300, #fcd34d);
}

/* Dark mode */
.app-dark .so-input {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-950, #020617);
  color: var(--p-text-color, #f8fafc);
}

.app-dark .so-input::placeholder {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-search-icon {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-spinner {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-panel-header {
  color: var(--p-surface-300, #cbd5e1);
}

.app-dark .so-panel {
  background: var(--p-surface-900, #1e293b);
  border-color: var(--p-surface-700, #334155);
}

.app-dark .so-panel-header {
  background: var(--p-surface-900, #1e293b);
  border-color: var(--p-surface-700, #334155);
}

.app-dark .so-option {
  border-color: var(--p-surface-800, #1e293b);
}

.app-dark .so-option[data-active="true"]:not(.so-option-disabled) {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .so-option[data-active="true"].so-option-disabled {
  background: var(--p-surface-900, #1e293b);
}

.app-dark .so-option-disabled,
.app-dark .so-option-added {
  background: var(--p-surface-900, #1e293b);
}

.app-dark .so-unit-factor {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-brand-inline {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-mobile-brand {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-brand-cell {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-added-status {
  color: var(--p-green-400, #4ade80);
}

.app-dark .so-empty {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-option-danger:not(.so-option-added) {
  border-bottom-color: var(--p-red-700, #b91c1c);
}

.app-dark .so-option-warn:not(.so-option-added) {
  border-bottom-color: var(--p-amber-700, #b45309);
}

@media (prefers-reduced-motion: reduce) {
  .so-option,
  .so-input,
  .so-panel-enter-active,
  .so-panel-leave-active {
    transition: none;
  }

  .so-panel-enter-from,
  .so-panel-leave-to {
    transform: none;
  }
}
</style>
