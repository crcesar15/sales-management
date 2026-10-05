<script setup lang="ts">
import { Badge, Button, Tag, useToast } from "primevue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useVariantClient } from "@composables/useVariantClient";
import type { StockAdjustmentVariantOption, StockAdjustmentVariantSearchResult } from "@app-types/stock-adjustment-types";

const props = defineProps<{
  modelValue: StockAdjustmentVariantSearchResult | null;
  storeId: number | null;
  invalid?: boolean;
  labelledby?: string;
  errorMessageId?: string;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: StockAdjustmentVariantSearchResult | null): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { searchVariantsApi } = useVariantClient();

const query = ref("");
const searchResults = ref<StockAdjustmentVariantSearchResult[]>([]);
const searchLoading = ref(false);
const searchFailed = ref(false);
const isOpen = ref(false);
const activeIndex = ref(-1);
const selectionAnnouncement = ref("");
const rootRef = useTemplateRef<HTMLElement>("root");
const inputRef = useTemplateRef<HTMLInputElement>("input");
const listRef = useTemplateRef<HTMLElement>("list");
const selectionRef = useTemplateRef<HTMLElement>("selection");

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;

function optionLabel(option: StockAdjustmentVariantOption): string {
  return option.option_name ? `${option.option_name}: ${option.value}` : option.value;
}

function variantLabel(variant: StockAdjustmentVariantSearchResult): string {
  const productName = variant.product?.name ?? "—";
  const identity = variant.option_values ? `, ${variant.option_values}` : "";
  return `${productName}${identity}`;
}

function stockSeverity(variant: StockAdjustmentVariantSearchResult): "success" | "warn" | "danger" {
  if (variant.stock <= 0) return "danger";
  if (variant.minimum_stock_level !== null && variant.stock <= variant.minimum_stock_level) return "warn";
  return "success";
}

function stockLabel(variant: StockAdjustmentVariantSearchResult): string {
  if (variant.stock <= 0) return t("Out of stock");
  return `${t("In stock")}: ${variant.stock}`;
}

function findNextIndex(startIndex: number, direction: 1 | -1): number {
  const count = searchResults.value.length;
  if (count === 0) return -1;

  return startIndex === -1 ? (direction === 1 ? 0 : count - 1) : (startIndex + direction + count) % count;
}

function scrollActiveIntoView() {
  nextTick(() => {
    listRef.value?.querySelector<HTMLElement>('[data-active="true"]')?.scrollIntoView({ block: "nearest" });
  });
}

function moveActiveOption(direction: 1 | -1) {
  activeIndex.value = findNextIndex(activeIndex.value, direction);
  if (activeIndex.value >= 0) scrollActiveIntoView();
}

async function performSearch() {
  const filter = query.value.trim();
  if (filter.length < 2 || props.storeId === null) {
    searchResults.value = [];
    searchLoading.value = false;
    return;
  }

  searchLoading.value = true;
  searchFailed.value = false;
  const requestId = ++currentRequestId;

  try {
    const response = await searchVariantsApi<StockAdjustmentVariantSearchResult>(filter, { storeId: props.storeId });
    if (requestId !== currentRequestId) return;

    searchResults.value = Array.isArray(response.data.data) ? response.data.data : [];
    activeIndex.value = findNextIndex(-1, 1);
  } catch {
    if (requestId !== currentRequestId) return;

    searchResults.value = [];
    searchFailed.value = true;
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000 });
  } finally {
    if (requestId === currentRequestId) searchLoading.value = false;
  }
}

watch(query, () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  currentRequestId++;
  searchResults.value = [];
  searchFailed.value = false;
  activeIndex.value = -1;
  debounceTimer = setTimeout(performSearch, 300);
});

watch(
  () => props.storeId,
  () => {
    currentRequestId++;
    query.value = "";
    searchResults.value = [];
    searchFailed.value = false;
    searchLoading.value = false;
    closePanel();
  },
);

function openPanel() {
  if (props.storeId !== null) isOpen.value = true;
}

function closePanel() {
  isOpen.value = false;
  activeIndex.value = -1;
}

function onQueryInput() {
  if (props.modelValue) emit("update:modelValue", null);
  openPanel();
}

function onInputFocus() {
  if (query.value.trim().length >= 2 && searchResults.value.length > 0) openPanel();
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === "ArrowDown" && searchResults.value.length > 0) {
    event.preventDefault();
    openPanel();
    moveActiveOption(1);
  } else if (event.key === "ArrowUp" && searchResults.value.length > 0) {
    event.preventDefault();
    openPanel();
    moveActiveOption(-1);
  } else if (event.key === "Escape" && isOpen.value) {
    event.preventDefault();
    closePanel();
  } else if (event.key === "Enter" && isOpen.value && activeIndex.value >= 0) {
    const variant = searchResults.value[activeIndex.value];
    if (variant) {
      event.preventDefault();
      selectVariant(variant);
    }
  }
}

function selectVariant(variant: StockAdjustmentVariantSearchResult) {
  emit("update:modelValue", variant);
  selectionAnnouncement.value = `${t("Product selected")}: ${variantLabel(variant)}`;
  query.value = "";
  searchResults.value = [];
  closePanel();
  nextTick(() => selectionRef.value?.focus());
}

function clearSelection() {
  currentRequestId++;
  query.value = "";
  searchResults.value = [];
  searchFailed.value = false;
  emit("update:modelValue", null);
  selectionAnnouncement.value = t("Product selection cleared");
  closePanel();
  nextTick(() => inputRef.value?.focus());
}

function onOutsideClick(event: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(event.target as Node)) closePanel();
}

onMounted(() => document.addEventListener("mousedown", onOutsideClick));

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onOutsideClick);
  if (debounceTimer) clearTimeout(debounceTimer);
});

const isPanelVisible = computed(() => isOpen.value && !searchLoading.value && (searchResults.value.length > 0 || showEmpty.value));
const showEmpty = computed(
  () => isOpen.value && !searchLoading.value && !searchFailed.value && query.value.trim().length >= 2 && searchResults.value.length === 0,
);
const searchAnnouncement = computed(() => {
  if (searchLoading.value) return t("Searching products");
  if (searchFailed.value) return t("Product search failed");
  if (query.value.trim().length < 2) return "";

  return t("Product options available", searchResults.value.length);
});
</script>

<template>
  <div ref="root" class="stock-adjustment-picker">
    <span class="sr-only" role="status" aria-live="polite">{{ selectionAnnouncement }}</span>
    <span class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ searchAnnouncement }}</span>

    <div
      v-if="modelValue"
      ref="selection"
      class="stock-adjustment-picker-selection"
      role="group"
      tabindex="-1"
      :aria-labelledby="labelledby"
      :aria-describedby="invalid ? errorMessageId : undefined"
    >
      <div class="stock-adjustment-picker-selection-content">
        <div class="stock-adjustment-picker-selection-heading">
          <span class="stock-adjustment-picker-selection-name">{{ modelValue.product?.name ?? "—" }}</span>
          <div v-if="modelValue.variant_options.length" class="stock-adjustment-picker-badges">
            <Badge
              v-for="option in modelValue.variant_options"
              :key="`${option.option_name}-${option.value}`"
              :value="optionLabel(option)"
              severity="primary"
            />
          </div>
        </div>

        <dl class="stock-adjustment-picker-details">
          <div class="stock-adjustment-picker-detail">
            <dt>{{ t("Brand") }}:</dt>
            <dd>{{ modelValue.product?.brand?.name ?? "—" }}</dd>
          </div>
          <div class="stock-adjustment-picker-detail">
            <dt>{{ t("Base unit") }}:</dt>
            <dd>{{ modelValue.product?.measurement_unit?.name ?? "—" }}</dd>
          </div>
          <div class="stock-adjustment-picker-detail">
            <dt>{{ t("Stock") }}:</dt>
            <dd><Tag :value="stockLabel(modelValue)" :severity="stockSeverity(modelValue)" rounded /></dd>
          </div>
          <div v-if="modelValue.identifier" class="stock-adjustment-picker-detail">
            <dt>{{ t("Identifier") }}:</dt>
            <dd>{{ modelValue.identifier }}</dd>
          </div>
          <div v-if="modelValue.has_expiration" class="stock-adjustment-picker-detail">
            <dt>{{ t("Expiry Date") }}:</dt>
            <dd>{{ t("Required") }}</dd>
          </div>
        </dl>
      </div>
      <Button
        type="button"
        v-tooltip.top="t('Clear product selection')"
        icon="fa fa-times"
        text
        rounded
        severity="secondary"
        :aria-label="t('Clear product selection')"
        @click="clearSelection"
      />
    </div>

    <div v-else class="stock-adjustment-picker-combobox">
      <i class="fa fa-search stock-adjustment-picker-search-icon" aria-hidden="true" />
      <input
        id="stock-adjustment-product-search"
        ref="input"
        v-model="query"
        type="text"
        autocomplete="off"
        role="combobox"
        :disabled="storeId === null"
        :placeholder="storeId === null ? t('Select a store first') : t('Search product...')"
        :aria-expanded="isPanelVisible"
        :aria-busy="searchLoading"
        :aria-invalid="invalid || undefined"
        aria-autocomplete="list"
        :aria-controls="isPanelVisible ? 'stock-adjustment-product-results' : undefined"
        :aria-activedescendant="isPanelVisible && activeIndex >= 0 ? `stock-adjustment-product-option-${activeIndex}` : undefined"
        :aria-describedby="invalid ? errorMessageId : undefined"
        :class="['stock-adjustment-picker-input', { 'stock-adjustment-picker-input-invalid': invalid }]"
        @focus="onInputFocus"
        @input="onQueryInput"
        @keydown="onKeydown"
      />
      <i v-if="searchLoading" class="fa fa-spinner fa-spin stock-adjustment-picker-spinner" aria-hidden="true" />

      <Transition name="stock-adjustment-picker-panel">
        <div
          v-if="isPanelVisible"
          id="stock-adjustment-product-results"
          ref="list"
          class="stock-adjustment-picker-panel"
          role="listbox"
          :aria-label="t('Product search results')"
        >
          <div class="stock-adjustment-picker-header">
            <span>{{ t("Product") }}</span>
            <span>{{ t("Brand") }}</span>
            <span>{{ t("Base unit") }}</span>
            <span>{{ t("Stock") }}</span>
            <span>{{ t("Identifier") }}</span>
          </div>

          <div
            v-for="(variant, index) in searchResults"
            :id="`stock-adjustment-product-option-${index}`"
            :key="variant.id"
            class="stock-adjustment-picker-option"
            role="option"
            :aria-selected="index === activeIndex"
            :data-active="index === activeIndex"
            @mouseenter="activeIndex = index"
            @click="selectVariant(variant)"
          >
            <div class="stock-adjustment-picker-row stock-adjustment-picker-row-desktop">
              <div class="stock-adjustment-picker-product">
                <span class="stock-adjustment-picker-product-name">{{ variant.product?.name ?? "—" }}</span>
                <div v-if="variant.variant_options.length" class="stock-adjustment-picker-badges">
                  <Badge
                    v-for="option in variant.variant_options"
                    :key="`${option.option_name}-${option.value}`"
                    :value="optionLabel(option)"
                    severity="primary"
                  />
                </div>
              </div>
              <span class="stock-adjustment-picker-muted">{{ variant.product?.brand?.name ?? "—" }}</span>
              <span>{{ variant.product?.measurement_unit?.name ?? "—" }}</span>
              <Tag :value="stockLabel(variant)" :severity="stockSeverity(variant)" rounded />
              <span class="stock-adjustment-picker-muted">{{ variant.identifier ?? "—" }}</span>
            </div>

            <div class="stock-adjustment-picker-row stock-adjustment-picker-row-mobile">
              <div class="stock-adjustment-picker-mobile-heading">
                <div class="stock-adjustment-picker-product">
                  <span class="stock-adjustment-picker-product-name">{{ variant.product?.name ?? "—" }}</span>
                  <div v-if="variant.variant_options.length" class="stock-adjustment-picker-badges">
                    <Badge
                      v-for="option in variant.variant_options"
                      :key="`${option.option_name}-${option.value}`"
                      :value="optionLabel(option)"
                      severity="primary"
                    />
                  </div>
                </div>
                <Tag :value="stockLabel(variant)" :severity="stockSeverity(variant)" rounded />
              </div>
              <span v-if="variant.product?.brand?.name" class="stock-adjustment-picker-muted">
                <i class="fa fa-tag" aria-hidden="true" /> {{ variant.product.brand.name }}
              </span>
              <div class="stock-adjustment-picker-mobile-details">
                <span><i class="fa fa-weight-hanging" aria-hidden="true" /> {{ variant.product?.measurement_unit?.name ?? "—" }}</span>
                <span v-if="variant.identifier">{{ variant.identifier }}</span>
              </div>
            </div>
          </div>

          <div v-if="showEmpty" class="stock-adjustment-picker-empty">
            <i class="fa fa-magnifying-glass" aria-hidden="true" />
            <span>{{ t("No results found") }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style>
.stock-adjustment-picker {
  width: 100%;
}

.stock-adjustment-picker-combobox {
  position: relative;
}

.stock-adjustment-picker-selection {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 6px;
  background: var(--p-surface-50, #f8fafc);
}

.stock-adjustment-picker-selection-content,
.stock-adjustment-picker-product {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 0.375rem;
}

.stock-adjustment-picker-selection-heading,
.stock-adjustment-picker-badges,
.stock-adjustment-picker-details,
.stock-adjustment-picker-mobile-details {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.stock-adjustment-picker-selection-name,
.stock-adjustment-picker-product-name {
  overflow: hidden;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stock-adjustment-picker-selection-name {
  font-size: 1rem;
  font-weight: 700;
}

.stock-adjustment-picker-details {
  gap: 0.5rem 1rem;
  margin: 0;
}

.stock-adjustment-picker-detail {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stock-adjustment-picker-detail dt,
.stock-adjustment-picker-muted {
  color: var(--p-surface-600, #475569);
}

.stock-adjustment-picker-detail dt {
  font-size: 0.875rem;
  font-weight: 700;
}

.stock-adjustment-picker-detail dd {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
}

.stock-adjustment-picker-search-icon,
.stock-adjustment-picker-spinner {
  position: absolute;
  top: 50%;
  z-index: 1;
  color: var(--p-surface-600, #475569);
  font-size: 0.875rem;
  pointer-events: none;
  transform: translateY(-50%);
}

.stock-adjustment-picker-search-icon {
  left: 0.75rem;
}

.stock-adjustment-picker-spinner {
  right: 0.75rem;
}

.stock-adjustment-picker-input {
  width: 100%;
  min-height: 44px;
  padding: 0.625rem 2.25rem;
  border: 1px solid var(--p-inputtext-border-color, #cbd5e1);
  border-radius: 6px;
  background: var(--p-inputtext-background, #ffffff);
  color: var(--p-inputtext-color, #1e293b);
  font: inherit;
  line-height: 1.5;
}

.stock-adjustment-picker-input:focus {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.stock-adjustment-picker-input-invalid {
  border-color: var(--p-red-500, #ef4444);
}

.stock-adjustment-picker-panel {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  left: 0;
  z-index: 50;
  max-height: 420px;
  overflow: auto;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 6px;
  background: var(--p-overlay-background, #ffffff);
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 10%);
}

.stock-adjustment-picker-header,
.stock-adjustment-picker-row-desktop {
  display: none;
}

.stock-adjustment-picker-option {
  border-bottom: 1px solid var(--p-surface-100, #f1f5f9);
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.stock-adjustment-picker-option:last-of-type {
  border-bottom: 0;
}

.stock-adjustment-picker-option[data-active="true"] {
  background: var(--p-surface-100, #f1f5f9);
  box-shadow: inset 0 0 0 1px var(--p-primary-color, #00539b);
}

.stock-adjustment-picker-row {
  min-height: 80px;
  padding: 0.5rem 0.75rem;
}

.stock-adjustment-picker-row-mobile {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stock-adjustment-picker-mobile-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.stock-adjustment-picker-mobile-details {
  color: var(--p-surface-600, #475569);
  font-size: 0.875rem;
}

.stock-adjustment-picker-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.75rem 1rem;
  color: var(--p-surface-600, #475569);
}

.stock-adjustment-picker-panel-enter-active,
.stock-adjustment-picker-panel-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.stock-adjustment-picker-panel-enter-from,
.stock-adjustment-picker-panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (min-width: 1024px) {
  .stock-adjustment-picker-header,
  .stock-adjustment-picker-row-desktop {
    display: grid;
    grid-template-columns: 4fr 2fr 2fr 2fr 2fr;
    gap: 0.5rem;
  }

  .stock-adjustment-picker-header {
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
    background: var(--p-overlay-background, #ffffff);
    color: var(--p-surface-700, #334155);
    font-size: 0.875rem;
    font-weight: 600;
  }

  .stock-adjustment-picker-row {
    min-height: 60px;
  }

  .stock-adjustment-picker-row-desktop {
    align-items: center;
  }

  .stock-adjustment-picker-row-mobile {
    display: none;
  }
}

.app-dark .stock-adjustment-picker-selection,
.app-dark .stock-adjustment-picker-panel,
.app-dark .stock-adjustment-picker-header {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-900, #1e293b);
}

.app-dark .stock-adjustment-picker-input {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-950, #020617);
  color: var(--p-text-color, #f8fafc);
}

.app-dark .stock-adjustment-picker-option {
  border-color: var(--p-surface-800, #1e293b);
}

.app-dark .stock-adjustment-picker-option[data-active="true"] {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .stock-adjustment-picker-detail dt,
.app-dark .stock-adjustment-picker-muted,
.app-dark .stock-adjustment-picker-mobile-details,
.app-dark .stock-adjustment-picker-empty,
.app-dark .stock-adjustment-picker-search-icon,
.app-dark .stock-adjustment-picker-spinner {
  color: var(--p-surface-400, #94a3b8);
}

@media (max-width: 768px) {
  .stock-adjustment-picker-input {
    min-height: 48px;
    padding: 10px 44px;
    font-size: 16px;
  }

  .stock-adjustment-picker-details {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .stock-adjustment-picker-option,
  .stock-adjustment-picker-panel-enter-active,
  .stock-adjustment-picker-panel-leave-active {
    transition: none;
  }
}
</style>
