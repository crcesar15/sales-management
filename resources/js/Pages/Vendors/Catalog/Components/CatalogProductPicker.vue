<script setup lang="ts">
import { Badge, useToast } from "primevue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useVariantClient } from "@composables/useVariantClient";
import type { CatalogProductUnitOption, CatalogVariantSearchResult } from "@/Types/catalog-types";

interface UnitRow extends CatalogProductUnitOption {
  variant: CatalogVariantSearchResult;
}

const props = defineProps<{
  modelValue: CatalogProductUnitOption | null;
  addedKeys: Set<string>;
  disabled?: boolean;
  invalid?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: CatalogProductUnitOption | null): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { searchVariantsApi } = useVariantClient();

function buildUnitRows(variant: CatalogVariantSearchResult): UnitRow[] {
  const productName = variant.product?.name ?? "—";
  const baseUnitName = variant.product?.measurement_unit?.name ?? t("Base unit");
  const baseRow: UnitRow = {
    key: `${variant.id}:base`,
    product_variant_id: variant.id,
    unit_id: null,
    product_name: productName,
    brand_name: variant.product?.brand?.name ?? null,
    base_unit_name: variant.product?.measurement_unit?.name ?? null,
    variant_identity: variant.option_values,
    unit_name: baseUnitName,
    conversion_factor: 1,
    variant,
  };

  return [
    baseRow,
    ...(variant.purchase_units ?? []).map(
      (unit): UnitRow => ({
        ...baseRow,
        key: `${variant.id}:${unit.id}`,
        unit_id: unit.id,
        unit_name: unit.name,
        conversion_factor: unit.conversion_factor > 0 ? unit.conversion_factor : 1,
      }),
    ),
  ];
}

const query = ref("");
const searchResults = ref<CatalogVariantSearchResult[]>([]);
const searchLoading = ref(false);
const searchFailed = ref(false);
const isOpen = ref(false);
const activeIndex = ref(-1);
const selectionAnnouncement = ref("");
const flatRows = computed<UnitRow[]>(() => searchResults.value.flatMap(buildUnitRows));
const rootRef = useTemplateRef<HTMLElement>("root");
const inputRef = useTemplateRef<HTMLInputElement>("input");
const listRef = useTemplateRef<HTMLElement>("list");

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;
let suppressNextSearch = false;

function rowLabel(row: CatalogProductUnitOption): string {
  const identity = row.variant_identity ? `, ${row.variant_identity}` : "";
  return `${row.product_name}${identity} (${row.unit_name})`;
}

function isRowDisabled(row: UnitRow): boolean {
  return props.addedKeys.has(row.key);
}

function findNextAvailableIndex(startIndex: number, direction: 1 | -1): number {
  for (let offset = 1; offset <= flatRows.value.length; offset++) {
    const index =
      startIndex === -1
        ? direction === 1
          ? offset - 1
          : flatRows.value.length - offset
        : (startIndex + direction * offset + flatRows.value.length) % flatRows.value.length;
    const row = flatRows.value[index];
    if (row && !isRowDisabled(row)) return index;
  }

  return -1;
}

function scrollActiveIntoView() {
  nextTick(() => {
    listRef.value?.querySelector<HTMLElement>('[data-active="true"]')?.scrollIntoView({ block: "nearest" });
  });
}

function moveActiveOption(direction: 1 | -1) {
  activeIndex.value = findNextAvailableIndex(activeIndex.value, direction);
  if (activeIndex.value >= 0) scrollActiveIntoView();
}

async function performSearch() {
  const filter = query.value.trim();
  if (filter.length < 2 || props.disabled) {
    searchResults.value = [];
    searchLoading.value = false;
    return;
  }

  searchLoading.value = true;
  searchFailed.value = false;
  const requestId = ++currentRequestId;

  try {
    const response = await searchVariantsApi(filter, ["purchaseUnits"]);
    if (requestId !== currentRequestId) return;

    searchResults.value = Array.isArray(response.data.data) ? response.data.data : [];
    activeIndex.value = findNextAvailableIndex(-1, 1);
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
  if (suppressNextSearch) {
    suppressNextSearch = false;
    return;
  }

  if (debounceTimer) clearTimeout(debounceTimer);
  currentRequestId++;
  searchResults.value = [];
  searchFailed.value = false;
  activeIndex.value = -1;
  debounceTimer = setTimeout(performSearch, 300);
});

watch(
  () => props.modelValue,
  (value) => {
    if (props.disabled && value) {
      suppressNextSearch = true;
      query.value = rowLabel(value);
    }
  },
  { immediate: true },
);

function openPanel() {
  if (!props.disabled) isOpen.value = true;
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
  } else if (event.key === "Escape" && isOpen.value) {
    event.preventDefault();
    closePanel();
  } else if (event.key === "Enter" && isOpen.value && activeIndex.value >= 0) {
    const row = flatRows.value[activeIndex.value];
    if (row && !isRowDisabled(row)) {
      event.preventDefault();
      selectRow(row);
    }
  }
}

function selectRow(row: UnitRow) {
  const { variant: _variant, ...selection } = row;
  emit("update:modelValue", selection);
  selectionAnnouncement.value = `${t("Product selected")}: ${rowLabel(row)}`;
  suppressNextSearch = true;
  query.value = rowLabel(row);
  searchResults.value = [];
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
  <div ref="root" class="catalog-picker">
    <span class="sr-only" role="status" aria-live="polite">{{ selectionAnnouncement }}</span>
    <span class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ searchAnnouncement }}</span>

    <div class="catalog-picker-combobox">
      <i class="fa fa-search catalog-picker-search-icon" aria-hidden="true" />
      <input
        id="catalog-product-search"
        ref="input"
        v-model="query"
        type="text"
        autocomplete="off"
        role="combobox"
        :disabled="disabled"
        :placeholder="t('Search product...')"
        :aria-expanded="isPanelVisible"
        :aria-busy="searchLoading"
        aria-autocomplete="list"
        :aria-controls="isPanelVisible ? 'catalog-product-results' : undefined"
        :aria-activedescendant="isPanelVisible && activeIndex >= 0 ? `catalog-product-option-${activeIndex}` : undefined"
        :class="['catalog-picker-input', { 'catalog-picker-input-invalid': invalid }]"
        @focus="onInputFocus"
        @input="onQueryInput"
        @keydown="onKeydown"
      />
      <i v-if="searchLoading" class="fa fa-spinner fa-spin catalog-picker-spinner" aria-hidden="true" />

      <Transition name="catalog-picker-panel">
        <div
          v-if="isPanelVisible"
          id="catalog-product-results"
          ref="list"
          class="catalog-picker-panel"
          role="listbox"
          :aria-label="t('Product search results')"
        >
          <div class="catalog-picker-header">
            <span>{{ t("Product") }}</span>
            <span>{{ t("Brand") }}</span>
            <span>{{ t("Purchase Unit") }}</span>
          </div>

          <div
            v-for="(row, index) in flatRows"
            :id="`catalog-product-option-${index}`"
            :key="row.key"
            :class="['catalog-picker-option', isRowDisabled(row) ? 'catalog-picker-option-disabled' : '']"
            role="option"
            :aria-selected="index === activeIndex"
            :aria-disabled="isRowDisabled(row)"
            :data-active="index === activeIndex"
            @mouseenter="!isRowDisabled(row) && (activeIndex = index)"
            @click="!isRowDisabled(row) && selectRow(row)"
          >
            <div class="catalog-picker-row catalog-picker-row-desktop">
              <div class="catalog-picker-product">
                <span class="catalog-picker-product-name">{{ row.product_name }}</span>
                <Badge v-if="row.variant_identity" :value="row.variant_identity" severity="primary" class="!font-semibold" />
              </div>
              <span class="catalog-picker-brand">{{ row.brand_name ?? "—" }}</span>
              <div class="catalog-picker-unit">
                <span class="font-medium">{{ row.unit_name }}</span>
                <span v-if="row.conversion_factor !== 1" class="catalog-picker-unit-factor">×{{ row.conversion_factor }}</span>
                <span v-if="row.base_unit_name && row.conversion_factor !== 1" class="catalog-picker-base-unit">
                  {{ row.base_unit_name }}
                </span>
                <span v-if="isRowDisabled(row)" class="catalog-picker-added">{{ t("Already added") }}</span>
              </div>
            </div>

            <div class="catalog-picker-row catalog-picker-row-mobile">
              <div class="catalog-picker-mobile-top">
                <div class="catalog-picker-product">
                  <span class="catalog-picker-product-name">{{ row.product_name }}</span>
                  <Badge v-if="row.variant_identity" :value="row.variant_identity" severity="primary" class="!font-semibold !text-[14px]" />
                </div>
                <span v-if="isRowDisabled(row)" class="catalog-picker-added">{{ t("Already added") }}</span>
              </div>
              <span v-if="row.brand_name" class="catalog-picker-mobile-brand">
                <i class="fa fa-tag" aria-hidden="true" />
                {{ row.brand_name }}
              </span>
              <div class="catalog-picker-unit">
                <span class="font-medium">
                  <i class="fa fa-weight-hanging" aria-hidden="true" />
                  {{ row.unit_name }}
                </span>
                <span v-if="row.conversion_factor !== 1" class="catalog-picker-unit-factor">×{{ row.conversion_factor }}</span>
                <span v-if="row.base_unit_name && row.conversion_factor !== 1" class="catalog-picker-base-unit">
                  {{ row.base_unit_name }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="showEmpty" class="catalog-picker-empty">
            <i class="fa fa-magnifying-glass" aria-hidden="true" />
            <span>{{ t("No results found") }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style>
.catalog-picker {
  width: 100%;
}

.catalog-picker-combobox {
  position: relative;
}

.catalog-picker-search-icon,
.catalog-picker-spinner {
  position: absolute;
  top: 50%;
  z-index: 1;
  color: var(--p-surface-600, #475569);
  font-size: 0.875rem;
  pointer-events: none;
  transform: translateY(-50%);
}

.catalog-picker-search-icon {
  left: 0.75rem;
}

.catalog-picker-spinner {
  right: 0.75rem;
}

.catalog-picker-input {
  width: 100%;
  min-height: 44px;
  padding: 0.625rem 2.25rem;
  border: 1px solid var(--p-inputtext-border-color, #cbd5e1);
  border-radius: 6px;
  background: var(--p-inputtext-background, #ffffff);
  color: var(--p-inputtext-color, #1e293b);
  font: inherit;
  font-size: 1rem;
  line-height: 1.5;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.catalog-picker-input:focus {
  outline: none;
  border-color: var(--p-primary-color, #00539b);
  box-shadow: 0 0 0 2px var(--p-focus-ring-color, rgba(0, 83, 155, 0.25));
}

.catalog-picker-input-invalid {
  border-color: var(--p-red-500, #ef4444);
}

.catalog-picker-input:disabled {
  background: var(--p-surface-100, #f1f5f9);
  cursor: not-allowed;
}

.catalog-picker-panel {
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
  box-shadow:
    0 4px 6px -1px rgb(0 0 0 / 10%),
    0 2px 4px -2px rgb(0 0 0 / 10%);
}

.catalog-picker-header,
.catalog-picker-row-desktop {
  display: none;
}

.catalog-picker-option {
  border-bottom: 1px solid var(--p-surface-100, #f1f5f9);
  cursor: pointer;
  transition:
    background-color 0.12s ease,
    box-shadow 0.12s ease;
}

.catalog-picker-option:last-child {
  border-bottom: 0;
}

.catalog-picker-option[data-active="true"]:not(.catalog-picker-option-disabled) {
  background: var(--p-surface-100, #f1f5f9);
  box-shadow: inset 0 0 0 1px var(--p-primary-color, #00539b);
}

.catalog-picker-option-disabled {
  background: var(--p-surface-50, #f8fafc);
  cursor: default;
}

.catalog-picker-row {
  min-height: 80px;
  padding: 0.5rem 0.75rem;
}

.catalog-picker-row-mobile {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.catalog-picker-mobile-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.catalog-picker-product {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 0.5rem;
}

.catalog-picker-product-name,
.catalog-picker-brand {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.catalog-picker-product-name {
  min-width: 0;
  font-size: 1rem;
  font-weight: 600;
}

.catalog-picker-brand,
.catalog-picker-mobile-brand,
.catalog-picker-unit-factor,
.catalog-picker-base-unit {
  color: var(--p-surface-700, #334155);
}

.catalog-picker-mobile-brand,
.catalog-picker-unit {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 1rem;
}

.catalog-picker-added {
  flex: none;
  color: var(--p-orange-700, #c2410c);
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
}

.catalog-picker-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.75rem 1rem;
  color: var(--p-surface-600, #475569);
}

.catalog-picker-panel-enter-active,
.catalog-picker-panel-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}

.catalog-picker-panel-enter-from,
.catalog-picker-panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (min-width: 1024px) {
  .catalog-picker-header,
  .catalog-picker-row-desktop {
    display: grid;
    grid-template-columns: 5fr 3fr 3fr;
    gap: 0.5rem;
  }

  .catalog-picker-header {
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

  .catalog-picker-row {
    min-height: 60px;
  }

  .catalog-picker-row-desktop {
    align-items: center;
  }

  .catalog-picker-row-mobile {
    display: none;
  }
}

.app-dark .catalog-picker-input {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-950, #020617);
  color: var(--p-text-color, #f8fafc);
}

.app-dark .catalog-picker-panel,
.app-dark .catalog-picker-header {
  border-color: var(--p-surface-700, #334155);
  background: var(--p-surface-900, #1e293b);
}

.app-dark .catalog-picker-option {
  border-color: var(--p-surface-800, #1e293b);
}

.app-dark .catalog-picker-option[data-active="true"]:not(.catalog-picker-option-disabled),
.app-dark .catalog-picker-option-disabled {
  background: var(--p-surface-800, #1e293b);
}

.app-dark .catalog-picker-brand,
.app-dark .catalog-picker-mobile-brand,
.app-dark .catalog-picker-unit-factor,
.app-dark .catalog-picker-base-unit,
.app-dark .catalog-picker-empty,
.app-dark .catalog-picker-search-icon,
.app-dark .catalog-picker-spinner {
  color: var(--p-surface-400, #94a3b8);
}

.app-dark .catalog-picker-added {
  color: var(--p-orange-400, #fb923c);
}

@media (max-width: 768px) {
  .catalog-picker-input {
    min-height: 48px;
    padding: 10px 44px;
    font-size: 16px;
  }

  .catalog-picker-search-icon {
    left: 14px;
  }

  .catalog-picker-spinner {
    right: 14px;
  }

  .catalog-picker-search-icon,
  .catalog-picker-spinner {
    font-size: 16px;
  }

  .catalog-picker-product-name {
    font-size: 1.125rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .catalog-picker-input,
  .catalog-picker-option,
  .catalog-picker-panel-enter-active,
  .catalog-picker-panel-leave-active {
    transition: none;
  }
}
</style>
