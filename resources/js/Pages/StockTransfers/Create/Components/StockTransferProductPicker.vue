<script setup lang="ts">
import { Badge, Tag, useToast } from "primevue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useVariantClient } from "@composables/useVariantClient";
import type { StockTransferVariantSearchResult } from "@app-types/stock-transfer-types";

const props = defineProps<{
  storeId: number | null;
  addedVariantIds: Set<number>;
}>();

const emit = defineEmits<{
  (e: "select", variant: StockTransferVariantSearchResult): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { searchVariantsApi } = useVariantClient();

const query = ref("");
const searchResults = ref<StockTransferVariantSearchResult[]>([]);
const searchLoading = ref(false);
const searchFailed = ref(false);
const isOpen = ref(false);
const activeIndex = ref(-1);
const rootRef = useTemplateRef<HTMLElement>("root");
const inputRef = useTemplateRef<HTMLInputElement>("input");
const listRef = useTemplateRef<HTMLElement>("list");

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;

function optionLabel(option: StockTransferVariantSearchResult["variant_options"][number]): string {
  return option.option_name ? `${option.option_name}: ${option.value}` : option.value;
}

function unitName(variant: StockTransferVariantSearchResult): string {
  return variant.product?.measurement_unit?.name ?? t("Unit");
}

function stockLabel(variant: StockTransferVariantSearchResult): string {
  if (variant.stock <= 0) return t("Out of stock");

  return `${t("Available")}: ${variant.stock} ${unitName(variant)}`;
}

function stockSeverity(variant: StockTransferVariantSearchResult): "success" | "warn" | "danger" {
  if (variant.stock <= 0) return "danger";
  if (variant.minimum_stock_level !== null && variant.stock <= variant.minimum_stock_level) return "warn";

  return "success";
}

function isDisabled(variant: StockTransferVariantSearchResult): boolean {
  return variant.stock <= 0 || props.addedVariantIds.has(variant.id);
}

function findNextIndex(startIndex: number, direction: 1 | -1): number {
  const count = searchResults.value.length;
  if (count === 0) return -1;

  for (let offset = 1; offset <= count; offset++) {
    const index = (startIndex + direction * offset + count) % count;
    const variant = searchResults.value[index];
    if (variant && !isDisabled(variant)) return index;
  }

  return -1;
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
    const response = await searchVariantsApi<StockTransferVariantSearchResult>(filter, { storeId: props.storeId });
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

function selectVariant(variant: StockTransferVariantSearchResult) {
  if (isDisabled(variant)) return;

  emit("select", variant);
  query.value = "";
  searchResults.value = [];
  closePanel();
  nextTick(() => inputRef.value?.focus());
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

function onOutsideClick(event: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(event.target as Node)) closePanel();
}

onMounted(() => document.addEventListener("mousedown", onOutsideClick));

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onOutsideClick);
  if (debounceTimer) clearTimeout(debounceTimer);
});

const showEmpty = computed(
  () => isOpen.value && !searchLoading.value && !searchFailed.value && query.value.trim().length >= 2 && searchResults.value.length === 0,
);
const isPanelVisible = computed(() => isOpen.value && !searchLoading.value && (searchResults.value.length > 0 || showEmpty.value));
const searchAnnouncement = computed(() => {
  if (searchLoading.value) return t("Searching products");
  if (searchFailed.value) return t("Product search failed");
  if (query.value.trim().length < 2) return "";

  return t("Product options available", searchResults.value.length);
});
</script>

<template>
  <div ref="root" class="stock-transfer-picker">
    <span class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ searchAnnouncement }}</span>

    <div class="stock-transfer-picker-combobox">
      <i class="fa fa-search stock-transfer-picker-search-icon" aria-hidden="true" />
      <input
        id="stock-transfer-product-search"
        ref="input"
        v-model="query"
        type="text"
        autocomplete="off"
        role="combobox"
        :disabled="storeId === null"
        :placeholder="storeId === null ? t('Select a source store first') : t('Search product...')"
        :aria-expanded="isPanelVisible"
        :aria-busy="searchLoading"
        aria-autocomplete="list"
        :aria-controls="isPanelVisible ? 'stock-transfer-product-results' : undefined"
        :aria-activedescendant="isPanelVisible && activeIndex >= 0 ? `stock-transfer-product-option-${activeIndex}` : undefined"
        class="stock-transfer-picker-input"
        @focus="query.trim().length >= 2 && searchResults.length > 0 ? openPanel() : undefined"
        @input="openPanel"
        @keydown="onKeydown"
      />
      <i v-if="searchLoading" class="fa fa-spinner fa-spin stock-transfer-picker-spinner" aria-hidden="true" />

      <Transition name="stock-transfer-picker-panel">
        <div
          v-if="isPanelVisible"
          id="stock-transfer-product-results"
          ref="list"
          class="stock-transfer-picker-panel"
          role="listbox"
          :aria-label="t('Product search results')"
        >
          <div class="stock-transfer-picker-header">
            <span>{{ t("Product") }}</span>
            <span>{{ t("Brand") }}</span>
            <span>{{ t("Base unit") }}</span>
            <span>{{ t("Available") }}</span>
            <span>{{ t("Identifier") }}</span>
          </div>

          <button
            v-for="(variant, index) in searchResults"
            :id="`stock-transfer-product-option-${index}`"
            :key="variant.id"
            type="button"
            class="stock-transfer-picker-option"
            role="option"
            :aria-selected="index === activeIndex"
            :aria-disabled="isDisabled(variant)"
            :data-active="index === activeIndex"
            :disabled="isDisabled(variant)"
            @mouseenter="!isDisabled(variant) && (activeIndex = index)"
            @click="selectVariant(variant)"
          >
            <div class="stock-transfer-picker-row stock-transfer-picker-row-desktop">
              <div class="stock-transfer-picker-product">
                <span class="stock-transfer-picker-product-name">{{ variant.product?.name ?? "—" }}</span>
                <div v-if="variant.variant_options.length" class="stock-transfer-picker-badges">
                  <Badge
                    v-for="option in variant.variant_options"
                    :key="`${option.option_name}-${option.value}`"
                    :value="optionLabel(option)"
                    severity="primary"
                  />
                </div>
              </div>
              <span class="stock-transfer-picker-muted">{{ variant.product?.brand?.name ?? "—" }}</span>
              <span>{{ unitName(variant) }}</span>
              <Tag :value="stockLabel(variant)" :severity="stockSeverity(variant)" rounded />
              <span class="stock-transfer-picker-muted">{{ variant.identifier ?? "—" }}</span>
            </div>

            <div class="stock-transfer-picker-row stock-transfer-picker-row-mobile">
              <div class="stock-transfer-picker-mobile-heading">
                <div class="stock-transfer-picker-product">
                  <span class="stock-transfer-picker-product-name">{{ variant.product?.name ?? "—" }}</span>
                  <div v-if="variant.variant_options.length" class="stock-transfer-picker-badges">
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
              <span v-if="variant.product?.brand?.name" class="stock-transfer-picker-muted">
                <i class="fa fa-tag" aria-hidden="true" /> {{ variant.product.brand.name }}
              </span>
              <div class="stock-transfer-picker-mobile-details">
                <span><i class="fa fa-weight-hanging" aria-hidden="true" /> {{ t("Base unit") }}: {{ unitName(variant) }}</span>
                <span v-if="variant.identifier">{{ variant.identifier }}</span>
              </div>
            </div>

            <span v-if="addedVariantIds.has(variant.id)" class="stock-transfer-picker-option-message">{{ t("Product already added") }}</span>
          </button>

          <div v-if="showEmpty" class="stock-transfer-picker-empty">
            <i class="fa fa-magnifying-glass" aria-hidden="true" />
            <span>{{ t("No results found") }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.stock-transfer-picker {
  width: 100%;
}

.stock-transfer-picker-combobox {
  position: relative;
}

.stock-transfer-picker-search-icon,
.stock-transfer-picker-spinner {
  position: absolute;
  z-index: 1;
  color: var(--p-surface-600, #475569);
  font-size: 0.875rem;
  pointer-events: none;
  transform: translateY(-50%);
}

.stock-transfer-picker-search-icon {
  top: 50%;
  left: 0.75rem;
}

.stock-transfer-picker-spinner {
  top: 50%;
  right: 0.75rem;
}

.stock-transfer-picker-input {
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

.stock-transfer-picker-input:focus {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgb(0 83 155 / 25%));
}

.stock-transfer-picker-panel {
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

.stock-transfer-picker-header,
.stock-transfer-picker-row-desktop {
  display: none;
}

.stock-transfer-picker-option {
  display: block;
  width: 100%;
  border: 0;
  border-bottom: 1px solid var(--p-surface-100, #f1f5f9);
  background: transparent;
  color: inherit;
  cursor: pointer;
  text-align: left;
  transition: background-color 0.15s ease;
}

.stock-transfer-picker-option:last-of-type {
  border-bottom: 0;
}

.stock-transfer-picker-option:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.stock-transfer-picker-option[data-active="true"] {
  background: var(--p-surface-100, #f1f5f9);
  box-shadow: inset 0 0 0 1px var(--p-primary-color, #00539b);
}

.stock-transfer-picker-row {
  min-height: 80px;
  padding: 0.5rem 0.75rem;
}

.stock-transfer-picker-row-mobile,
.stock-transfer-picker-product,
.stock-transfer-picker-mobile-heading,
.stock-transfer-picker-badges,
.stock-transfer-picker-mobile-details {
  display: flex;
}

.stock-transfer-picker-row-mobile,
.stock-transfer-picker-product {
  flex-direction: column;
}

.stock-transfer-picker-row-mobile {
  gap: 0.5rem;
}

.stock-transfer-picker-product {
  min-width: 0;
  flex: 1;
  gap: 0.375rem;
}

.stock-transfer-picker-mobile-heading {
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.stock-transfer-picker-badges,
.stock-transfer-picker-mobile-details {
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.stock-transfer-picker-product-name {
  overflow: hidden;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stock-transfer-picker-muted,
.stock-transfer-picker-mobile-details,
.stock-transfer-picker-option-message {
  color: var(--p-surface-600, #475569);
}

.stock-transfer-picker-mobile-details,
.stock-transfer-picker-option-message {
  font-size: 0.875rem;
}

.stock-transfer-picker-option-message {
  display: block;
  padding: 0 0.75rem 0.5rem;
}

.stock-transfer-picker-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.75rem 1rem;
  color: var(--p-surface-600, #475569);
}

.stock-transfer-picker-panel-enter-active,
.stock-transfer-picker-panel-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.stock-transfer-picker-panel-enter-from,
.stock-transfer-picker-panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (min-width: 1024px) {
  .stock-transfer-picker-header,
  .stock-transfer-picker-row-desktop {
    display: grid;
    grid-template-columns: 4fr 2fr 2fr 2fr 2fr;
    gap: 0.5rem;
  }

  .stock-transfer-picker-header {
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

  .stock-transfer-picker-row {
    min-height: 60px;
  }

  .stock-transfer-picker-row-desktop {
    align-items: center;
  }

  .stock-transfer-picker-row-mobile {
    display: none;
  }
}

.app-dark .stock-transfer-picker-panel,
.app-dark .stock-transfer-picker-header {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-900, #1e293b);
}

.app-dark .stock-transfer-picker-input {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-950, #020617);
  color: var(--p-text-color, #f8fafc);
}

.app-dark .stock-transfer-picker-option {
  border-color: var(--p-surface-800, #1e293b);
}

.app-dark .stock-transfer-picker-option[data-active="true"] {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .stock-transfer-picker-muted,
.app-dark .stock-transfer-picker-mobile-details,
.app-dark .stock-transfer-picker-option-message,
.app-dark .stock-transfer-picker-empty,
.app-dark .stock-transfer-picker-search-icon,
.app-dark .stock-transfer-picker-spinner {
  color: var(--p-surface-400, #94a3b8);
}

@media (max-width: 768px) {
  .stock-transfer-picker-input {
    min-height: 48px;
    padding: 10px 44px;
    font-size: 16px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .stock-transfer-picker-option,
  .stock-transfer-picker-panel-enter-active,
  .stock-transfer-picker-panel-leave-active {
    transition: none;
  }
}
</style>
