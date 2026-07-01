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

/** Live available (in this row's unit) = floor(remainingBase / cf). Falls back to snapshot. */
function availableInUnit(row: UnitRow): number | null {
  const base = props.getRemainingBase?.(row.variantId) ?? row.baseStock;
  if (base === null) return null;
  const cf = row.conversionFactor > 0 ? row.conversionFactor : 1;
  return Math.floor(base / cf);
}

/** One severity rule, applied identically here and in the table (unit-aware threshold). */
function getStockSeverity(row: UnitRow): "success" | "warn" | "danger" {
  const avail = availableInUnit(row);
  if (avail === null) return "success";
  if (avail <= 0) return "danger";
  if (row.minStockBase !== null && row.minStockBase > 0) {
    const minInUnit = Math.ceil(row.minStockBase / row.conversionFactor);
    if (avail <= minInUnit) return "warn";
  }
  return "success";
}

function getStockLabel(row: UnitRow): string {
  const avail = availableInUnit(row);
  if (avail === null) return "—";
  if (avail === 0) return t("Out of stock");
  return `${t("Available")}: ${String(avail)}`;
}

function isRowDisabled(row: UnitRow): boolean {
  return props.addedKeys.has(row.key) || getStockSeverity(row) === "danger";
}

const query = ref("");
const searchResults = ref<VariantSearchResult[]>([]);
const searchLoading = ref(false);
const isOpen = ref(false);
const activeIndex = ref(-1);

const rootRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;

async function performSearch() {
  const q = query.value.trim();
  if (q.length < 2 || props.storeId == null) {
    searchResults.value = [];
    return;
  }
  searchLoading.value = true;
  const reqId = ++currentRequestId;
  try {
    const response = await searchVariantsApi(q, props.storeId);
    if (reqId !== currentRequestId) return;
    const data = response.data?.data ?? [];
    searchResults.value = Array.isArray(data) ? (data as VariantSearchResult[]) : [];
    activeIndex.value = searchResults.value.length > 0 ? 0 : -1;
  } catch {
    if (reqId !== currentRequestId) return;
    searchResults.value = [];
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000 });
  } finally {
    if (reqId === currentRequestId) searchLoading.value = false;
  }
}

watch(query, () => {
  if (debounceTimer) clearTimeout(debounceTimer);
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
    if (!isOpen.value && flatRows.value.length > 0) {
      openPanel();
      return;
    }
    if (flatRows.value.length > 0) {
      activeIndex.value = (activeIndex.value + 1) % flatRows.value.length;
      scrollActiveIntoView();
    }
  } else if (event.key === "ArrowUp") {
    event.preventDefault();
    if (isOpen.value && flatRows.value.length > 0) {
      activeIndex.value = activeIndex.value <= 0 ? flatRows.value.length - 1 : activeIndex.value - 1;
      scrollActiveIntoView();
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
  const productName = variant.product?.name ?? variant.name ?? "—";
  const variantLabel = variant.variant_label ?? variant.identifier ?? null;
  // When the variant has no distinguishing label (default-only product),
  // show just the unit name — the product name is already on line 1 of the row.
  const displayLabel = variantLabel ? `${variantLabel} (${row.unitName})` : row.unitName;

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: productName,
    variant_label: displayLabel,
    sale_unit_id: row.unitId,
    quantity: 1,
    unit_price: row.price,
    conversion_factor: row.conversionFactor,
    line_total: row.price * 1,
    stock: row.baseStock,
    minimum_stock_level: row.minStockBase,
    sale_units: variant.sale_units ?? [],
    sale_unit: row.isBase ? null : { id: row.unitId as number, name: row.unitName, conversion_factor: row.conversionFactor },
  };

  emit("add", newItem);
  query.value = "";
  searchResults.value = [];
  closePanel();
  nextTick(() => inputRef.value?.focus());
}

const inputDisabled = computed(() => props.storeId == null);
const inputPlaceholder = computed(() => (props.storeId == null ? t("Select a store first") : t("Search product...")));

const showEmpty = computed(
  () => isOpen.value && !searchLoading.value && query.value.trim().length >= 2 && searchResults.value.length === 0,
);
</script>

<template>
  <div ref="rootRef" class="flex flex-col gap-2 mb-3">
    <label for="so-product-search">{{ t("Add Product") }}</label>
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
          :aria-expanded="isOpen"
          aria-autocomplete="list"
          :aria-activedescendant="activeIndex >= 0 ? `so-opt-${activeIndex}` : undefined"
          class="so-input"
          @focus="onInputFocus"
          @input="openPanel"
          @keydown="onKeydown"
        />
        <i v-if="searchLoading" class="fa fa-spinner fa-spin so-spinner" aria-hidden="true" />
      </div>

      <Transition name="so-panel">
        <div v-if="isOpen && (flatRows.length > 0 || showEmpty)" ref="listRef" class="so-panel" role="listbox">
          <div class="so-panel-header">
            <span class="so-col so-col-product">{{ t("Product") }}</span>
            <span class="so-col so-col-brand">{{ t("Brand") }}</span>
            <span class="so-col so-col-unit">{{ t("Sale Unit") }}</span>
            <span class="so-col so-col-price">{{ t("Price") }}</span>
            <span class="so-col so-col-stock">{{ t("Available") }}</span>
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
            :tabindex="isRowDisabled(row) ? -1 : 0"
            @mouseenter="activeIndex = rIndex"
            @click="!isRowDisabled(row) && addUnitRow(row)"
          >
            <!-- Desktop: grid row -->
            <div class="so-row so-row-desktop">
              <div class="so-col so-col-product so-product-cell">
                <span class="so-product-name">{{ row.variant.product?.name ?? row.variant.name }}</span>
                <div v-if="row.variant.option_values || row.variant.identifier" class="flex flex-wrap gap-1">
                  <Badge v-if="row.variant.option_values" :value="row.variant.option_values" severity="secondary" />
                  <Badge v-else-if="row.variant.identifier" :value="row.variant.identifier" severity="secondary" />
                </div>
              </div>
              <div class="so-col so-col-brand so-brand-cell">{{ row.variant.product?.brand?.name ?? "—" }}</div>
              <div class="so-col so-col-unit so-unit-cell">
                <span class="font-medium">{{ row.unitName }}</span>
                <span v-if="row.conversionFactor !== 1" class="so-unit-factor">×{{ row.conversionFactor }}</span>
              </div>
              <div class="so-col so-col-price so-price-cell">{{ formatCurrency(String(row.price)) }}</div>
              <div class="so-col so-col-stock so-stock-cell">
                <Tag :value="getStockLabel(row)" :severity="getStockSeverity(row)" class="text-xs" rounded />
                <span v-if="addedKeys.has(row.key)" class="so-added-check" aria-hidden="true">
                  <i class="fa fa-check" />
                </span>
              </div>
            </div>

            <!-- Mobile: stacked card -->
            <div class="so-row so-row-mobile">
              <div class="so-mobile-top">
                <div class="so-product-cell">
                  <span class="so-product-name">{{ row.variant.product?.name ?? row.variant.name }}</span>
                  <div class="flex flex-wrap gap-1">
                    <span v-if="row.variant.product?.brand?.name" class="so-brand-inline">{{ row.variant.product.brand.name }}</span>
                    <Badge v-if="row.variant.option_values" :value="row.variant.option_values" severity="secondary" />
                    <Badge v-else-if="row.variant.identifier" :value="row.variant.identifier" severity="secondary" />
                  </div>
                </div>
                <div class="so-mobile-right">
                  <span class="so-price-cell">{{ formatCurrency(String(row.price)) }}</span>
                </div>
              </div>
              <div class="so-mobile-bottom">
                <div class="so-unit-cell">
                  <span class="font-medium">{{ row.unitName }}</span>
                  <span v-if="row.conversionFactor !== 1" class="so-unit-factor">×{{ row.conversionFactor }}</span>
                </div>
                <Tag :value="getStockLabel(row)" :severity="getStockSeverity(row)" class="text-xs" rounded />
                <span v-if="addedKeys.has(row.key)" class="so-added-check" aria-hidden="true">
                  <i class="fa fa-check" />
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
  color: var(--p-surface-500, #64748b);
  font-size: 0.875rem;
  pointer-events: none;
  z-index: 1;
}

.so-input {
  width: 100%;
  padding: 0.625rem 2.25rem 0.625rem 2.25rem;
  border: 1px solid var(--p-inputtext-border-color, #cbd5e1);
  border-radius: 6px;
  background: var(--p-inputtext-background, #ffffff);
  color: var(--p-inputtext-color, #1e293b);
  font-family: inherit;
  font-size: 0.875rem;
  line-height: 1.5;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.so-input::placeholder {
  color: var(--p-inputtext-placeholder-color, #94a3b8);
}

.so-input:focus {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.so-input:disabled {
  background: var(--p-surface-100, #f1f5f9);
  color: var(--p-surface-500, #64748b);
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
  grid-template-columns: 4fr 2fr 2fr 2fr 2fr;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--p-surface-500, #64748b);
  border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
  position: sticky;
  top: 0;
  background: var(--p-overlay-background, #ffffff);
  z-index: 1;
}

.so-option {
  border-bottom: 1px solid var(--p-surface-100, #f1f5f9);
  cursor: pointer;
  transition: background-color 0.12s ease;
}

.so-option:last-child {
  border-bottom: none;
}

.so-option[data-active="true"]:not(.so-option-disabled) {
  background: var(--p-surface-100, #f1f5f9);
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
  opacity: 0.65;
}

.so-option-added {
  cursor: default;
  opacity: 0.7;
}

.so-row {
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
  font-size: 0.875rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.so-brand-inline {
  font-size: 0.75rem;
  color: var(--p-surface-500, #64748b);
}

.so-brand-cell {
  font-size: 0.875rem;
  color: var(--p-surface-500, #64748b);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.so-unit-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.so-unit-factor {
  color: var(--p-surface-500, #64748b);
  font-size: 0.75rem;
  font-weight: 400;
}

.so-price-cell {
  font-weight: 600;
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
}

.so-stock-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.so-mobile-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.so-mobile-top .so-product-cell {
  flex: 1;
  min-width: 0;
}

.so-mobile-right {
  display: flex;
  flex-shrink: 0;
}

.so-mobile-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.so-added-check {
  display: inline-flex;
  align-items: center;
  color: var(--p-surface-500, #64748b);
  font-size: 0.75rem;
}

.so-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.75rem 1rem;
  color: var(--p-surface-400, #94a3b8);
  font-size: 0.875rem;
}

.so-empty i {
  font-size: 1.5rem;
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
  .so-panel-header {
    display: grid;
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

.app-dark .so-unit-factor,
.app-dark .so-brand-inline,
.app-dark .so-brand-cell {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-added-check {
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
