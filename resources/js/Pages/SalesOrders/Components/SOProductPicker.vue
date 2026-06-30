<script setup lang="ts">
import { Tag, Badge, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useSalesOrderClient } from "@/Composables/useSalesOrderClient";
import type { SalesOrderLineItemForm, VariantSearchResult } from "@/Types/sales-order-types";

type LineItem = SalesOrderLineItemForm;

defineProps<{
  addedKeys: Set<string>;
}>();

const emit = defineEmits<{
  (e: "add", item: LineItem): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const toast = useToast();
const { searchVariantsApi } = useSalesOrderClient();

interface SaleUnitPill {
  key: string;
  id: number | null;
  name: string;
  conversion_factor: number;
  price: number;
  available: number | null;
  severity: "success" | "warn" | "danger";
  isBase: boolean;
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

function buildPills(variant: VariantSearchResult): SaleUnitPill[] {
  const baseStock = variant.stock ?? null;
  const minStock = variant.minimum_stock_level ?? null;
  const baseUnitName = variant.product?.measurement_unit?.name ?? t("Unit");
  const basePrice = variant.price ? parseFloat(String(variant.price)) : 0;

  const pills: SaleUnitPill[] = [];

  pills.push({
    key: `${variant.id}:base`,
    id: null,
    name: baseUnitName,
    conversion_factor: 1,
    price: basePrice,
    available: baseStock,
    severity: getStockSeverity(baseStock, minStock),
    isBase: true,
  });

  const saleUnits = variant.sale_units ?? [];
  for (const unit of saleUnits) {
    const cf = unit.conversion_factor > 0 ? unit.conversion_factor : 1;
    const avail = baseStock !== null ? Math.floor(baseStock / cf) : null;
    pills.push({
      key: `${variant.id}:${unit.id}`,
      id: unit.id,
      name: unit.name,
      conversion_factor: cf,
      price: parseFloat(String(unit.price)),
      available: avail,
      severity: getStockSeverity(avail, null),
      isBase: false,
    });
  }

  return pills;
}

function pillCaption(pill: SaleUnitPill): string {
  if (pill.available === null) return "—";
  return String(pill.available);
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
  if (q.length < 2) {
    searchResults.value = [];
    return;
  }
  searchLoading.value = true;
  const reqId = ++currentRequestId;
  try {
    const response = await searchVariantsApi(q);
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
    if (!isOpen.value && searchResults.value.length > 0) {
      openPanel();
      return;
    }
    if (searchResults.value.length > 0) {
      activeIndex.value = (activeIndex.value + 1) % searchResults.value.length;
      scrollActiveIntoView();
    }
  } else if (event.key === "ArrowUp") {
    event.preventDefault();
    if (isOpen.value && searchResults.value.length > 0) {
      activeIndex.value = activeIndex.value <= 0 ? searchResults.value.length - 1 : activeIndex.value - 1;
      scrollActiveIntoView();
    }
  } else if (event.key === "Escape") {
    if (isOpen.value) {
      event.preventDefault();
      closePanel();
    }
  } else if (event.key === "Enter") {
    if (isOpen.value && activeIndex.value >= 0 && activeIndex.value < searchResults.value.length) {
      event.preventDefault();
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

function addFromPill(variant: VariantSearchResult, pill: SaleUnitPill) {
  const productName = variant.product?.name ?? variant.name ?? "—";
  const variantLabel = variant.variant_label ?? variant.identifier ?? productName;

  const unitLabel = pill.name;
  const displayLabel = `${variantLabel} (${unitLabel})`;

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: productName,
    variant_label: displayLabel,
    sale_unit_id: pill.id,
    quantity: 1,
    unit_price: pill.price,
    conversion_factor: pill.conversion_factor,
    line_total: pill.price * 1,
    stock: variant.stock ?? null,
    minimum_stock_level: variant.minimum_stock_level ?? null,
    sale_units: variant.sale_units ?? [],
    sale_unit: pill.isBase
      ? null
      : { id: pill.id as number, name: pill.name, conversion_factor: pill.conversion_factor },
  };

  emit("add", newItem);
  query.value = "";
  searchResults.value = [];
  closePanel();
  nextTick(() => inputRef.value?.focus());
}

const showEmpty = computed(() => isOpen.value && !searchLoading.value && query.value.trim().length >= 2 && searchResults.value.length === 0);
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
          :placeholder="t('Search product...')"
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
        <div
          v-if="isOpen && (searchResults.length > 0 || showEmpty)"
          ref="listRef"
          class="so-panel"
          role="listbox"
        >
          <div
            class="so-panel-header"
          >
            <span class="so-col so-col-product">{{ t("Product") }}</span>
            <span class="so-col so-col-brand">{{ t("Brand") }}</span>
            <span class="so-col so-col-price">{{ t("Price") }}</span>
            <span class="so-col so-col-stock">{{ t("Stock") }}</span>
            <span class="so-col so-col-units">{{ t("Sale Units") }}</span>
          </div>

          <div
            v-for="(variant, vIndex) in searchResults"
            :id="`so-opt-${vIndex}`"
            :key="variant.id"
            class="so-option"
            role="option"
            :aria-selected="vIndex === activeIndex"
            :data-active="vIndex === activeIndex"
            @mouseenter="activeIndex = vIndex"
          >
            <!-- Desktop: grid row -->
            <div class="so-row so-row-desktop">
              <div class="so-col so-col-product so-product-cell">
                <span class="so-product-name">{{ variant.product?.name ?? variant.name }}</span>
                <div v-if="variant.option_values || variant.identifier" class="flex flex-wrap gap-1">
                  <Badge
                    v-if="variant.option_values"
                    :value="variant.option_values"
                    severity="secondary"
                  />
                  <Badge
                    v-else-if="variant.identifier"
                    :value="variant.identifier"
                    severity="secondary"
                  />
                </div>
              </div>
              <div class="so-col so-col-brand so-brand-cell">{{ variant.product?.brand?.name ?? "—" }}</div>
              <div class="so-col so-col-price so-price-cell">{{ formatCurrency(String(variant.price)) }}</div>
              <div class="so-col so-col-stock">
                <Tag
                  :value="getStockLabel(variant.stock)"
                  :severity="getStockSeverity(variant.stock, variant.minimum_stock_level)"
                  class="text-xs"
                  rounded
                />
              </div>
              <div class="so-col so-col-units so-pills-row">
                <div class="so-pills">
                  <button
                    v-for="pill in buildPills(variant)"
                    :key="pill.key"
                    type="button"
                    :disabled="addedKeys.has(pill.key)"
                    :class="[
                      'so-pill',
                      pill.severity === 'danger' ? 'so-pill-danger' : '',
                      pill.severity === 'warn' ? 'so-pill-warn' : '',
                      addedKeys.has(pill.key) ? 'so-pill-added' : '',
                    ]"
                    @click.stop="addFromPill(variant, pill)"
                  >
                    <span class="so-pill-name">{{ pill.name }}</span>
                    <span v-if="pill.conversion_factor !== 1" class="so-pill-factor">×{{ pill.conversion_factor }}</span>
                    <span class="so-pill-price">{{ formatCurrency(String(pill.price)) }}</span>
                    <span
                      :class="['so-pill-stock', pill.severity === 'danger' ? 'so-pill-stock-danger' : '']"
                      :title="t('Available stock')"
                    >
                      <i class="fa fa-boxes-stacked" aria-hidden="true" />
                      {{ pillCaption(pill) }}
                    </span>
                    <span v-if="addedKeys.has(pill.key)" class="so-pill-check" aria-hidden="true">
                      <i class="fa fa-check" />
                    </span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Mobile: stacked card -->
            <div class="so-row so-row-mobile">
              <div class="so-mobile-top">
                <div class="so-product-cell">
                  <span class="so-product-name">{{ variant.product?.name ?? variant.name }}</span>
                  <div v-if="variant.option_values || variant.identifier" class="flex flex-wrap gap-1">
                    <span v-if="variant.product?.brand?.name" class="so-brand-inline">{{ variant.product.brand.name }}</span>
                    <Badge
                      v-if="variant.option_values"
                      :value="variant.option_values"
                      severity="secondary"
                    />
                    <Badge
                      v-else-if="variant.identifier"
                      :value="variant.identifier"
                      severity="secondary"
                    />
                  </div>
                </div>
                <div class="so-mobile-right">
                  <span class="so-price-cell">{{ formatCurrency(String(variant.price)) }}</span>
                  <Tag
                    :value="getStockLabel(variant.stock)"
                    :severity="getStockSeverity(variant.stock, variant.minimum_stock_level)"
                    class="text-xs"
                  />
                </div>
              </div>
              <div class="so-pills">
                <button
                  v-for="pill in buildPills(variant)"
                  :key="pill.key"
                  type="button"
                  :disabled="addedKeys.has(pill.key)"
                  :class="[
                    'so-pill',
                    pill.severity === 'danger' ? 'so-pill-danger' : '',
                    pill.severity === 'warn' ? 'so-pill-warn' : '',
                    addedKeys.has(pill.key) ? 'so-pill-added' : '',
                  ]"
                  @click.stop="addFromPill(variant, pill)"
                >
                  <span class="so-pill-name">{{ pill.name }}</span>
                  <span v-if="pill.conversion_factor !== 1" class="so-pill-factor">×{{ pill.conversion_factor }}</span>
                  <span class="so-pill-price">{{ formatCurrency(String(pill.price)) }}</span>
                  <span
                    :class="['so-pill-stock', pill.severity === 'danger' ? 'so-pill-stock-danger' : '']"
                    :title="t('Available stock')"
                  >
                    <i class="fa fa-boxes-stacked" aria-hidden="true" />
                    {{ pillCaption(pill) }}
                  </span>
                  <span v-if="addedKeys.has(pill.key)" class="so-pill-check" aria-hidden="true">
                    <i class="fa fa-check" />
                  </span>
                </button>
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
  cursor: default;
  transition: background-color 0.12s ease;
}

.so-option:last-child {
  border-bottom: none;
}

.so-option[data-active="true"] {
  background: var(--p-surface-100, #f1f5f9);
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
  gap: 0.5rem;
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

.so-price-cell {
  font-weight: 600;
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
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
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
  flex-shrink: 0;
}

.so-mobile-right .so-price-cell {
  font-size: 0.875rem;
}

.so-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.so-pills-row {
  display: flex;
  align-items: center;
}

/* Pills */
.so-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.3125rem 0.5rem;
  border: 1px solid var(--p-surface-300, #cbd5e1);
  border-radius: 6px;
  background: transparent;
  color: var(--p-text-color, #1e293b);
  font-size: 0.75rem;
  line-height: 1.25;
  font-weight: 500;
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
  user-select: none;
  max-width: 100%;
}

.so-pill:hover:not(:disabled):not(.so-pill-added) {
  background: var(--p-surface-100, #f1f5f9);
  border-color: var(--p-primary-color, #00539b);
  color: var(--p-primary-color, #00539b);
}

.so-pill:active:not(:disabled):not(.so-pill-added) {
  background: var(--p-surface-200, #e2e8f0);
}

.so-pill:focus-visible {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.so-pill:disabled {
  cursor: default;
  opacity: 0.7;
}

.so-pill-added {
  background: var(--p-surface-100, #f1f5f9);
  border-color: var(--p-surface-300, #cbd5e1);
  color: var(--p-surface-500, #64748b);
  opacity: 0.75;
}

.so-pill-name {
  font-weight: 600;
}

.so-pill-factor {
  color: var(--p-surface-500, #64748b);
  font-weight: 400;
}

.so-pill-price {
  font-variant-numeric: tabular-nums;
  color: var(--p-surface-700, #334155);
}

.so-pill-stock {
  display: inline-flex;
  align-items: center;
  gap: 0.1875rem;
  color: var(--p-surface-500, #64748b);
  font-weight: 400;
}

.so-pill-stock i {
  font-size: 0.625rem;
}

.so-pill-stock-danger {
  color: var(--p-red-500, #ef4444);
  font-weight: 600;
}

.so-pill-danger:not(.so-pill-added) {
  border-color: var(--p-red-300, #fca5a5);
}

.so-pill-warn:not(.so-pill-added) {
  border-color: var(--p-amber-300, #fcd34d);
}

.so-pill-check {
  display: inline-flex;
  align-items: center;
  color: var(--p-surface-500, #64748b);
  font-size: 0.6875rem;
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
    align-items: start;
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

  .so-col-price {
    align-self: center;
  }

  .so-col-stock {
    align-self: center;
  }

  .so-col-units {
    align-self: center;
  }
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

.app-dark .so-option[data-active="true"] {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .so-pill {
  border-color: var(--p-surface-700, #334155);
  color: var(--p-text-color, #f8fafc);
}

.app-dark .so-pill:hover:not(:disabled):not(.so-pill-added) {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .so-pill:active:not(:disabled):not(.so-pill-added) {
  background: var(--p-surface-700, #334155);
}

.app-dark .so-pill-added {
  background: var(--p-surface-800, #1e293b);
  border-color: var(--p-surface-700, #334155);
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-pill-price {
  color: var(--p-surface-300, #cbd5e1);
}

.app-dark .so-pill-stock {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .so-pill-stock-danger {
  color: var(--p-red-400, #f87171);
}

.app-dark .so-pill-danger:not(.so-pill-added) {
  border-color: var(--p-red-700, #b91c1c);
}

.app-dark .so-pill-warn:not(.so-pill-added) {
  border-color: var(--p-amber-700, #b45309);
}

@media (prefers-reduced-motion: reduce) {
  .so-pill,
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