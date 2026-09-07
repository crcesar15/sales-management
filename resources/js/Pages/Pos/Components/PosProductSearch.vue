<script setup lang="ts">
import { Badge, Button, IconField, InputIcon, InputText, Message, Tag, useToast } from "primevue";
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useSalesOrderClient } from "@composables/useSalesOrderClient";
import type { PosCartItem } from "@/Types/pos";
import type { VariantSearchResult } from "@/Types/sales-order-types";

interface SellableUnit {
  key: string;
  variant: VariantSearchResult;
  saleUnitId: number | null;
  saleUnitName: string;
  conversionFactor: number;
  unitPrice: number;
}

const props = defineProps<{
  storeId: number | null;
  getRemainingBase: (variantId: number) => number | null;
}>();

const emit = defineEmits<{
  (e: "add", item: PosCartItem): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const toast = useToast();
const { searchVariantsApi } = useSalesOrderClient();

const query = ref("");
const results = ref<VariantSearchResult[]>([]);
const loading = ref(false);
const failed = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let requestId = 0;

const units = computed<SellableUnit[]>(() =>
  results.value.flatMap((variant) => {
    const baseUnitName = variant.product?.measurement_unit?.name ?? t("Unit");
    const basePrice = Number(variant.price) || 0;
    const baseUnit: SellableUnit = {
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
const showPrompt = computed(() => query.value.trim().length < 2);
const showEmpty = computed(() => shouldSearch.value && !loading.value && !failed.value && units.value.length === 0);

function remainingInUnit(unit: SellableUnit): number | null {
  const remainingBase = props.getRemainingBase(unit.variant.id) ?? unit.variant.stock ?? null;
  if (remainingBase === null) return null;

  return Math.floor(remainingBase / unit.conversionFactor);
}

function stockSeverity(unit: SellableUnit): "success" | "warn" | "danger" {
  const remaining = remainingInUnit(unit);
  if (remaining === null || remaining > (unit.variant.minimum_stock_level ?? 0)) return "success";
  if (remaining <= 0) return "danger";

  return "warn";
}

function stockLabel(unit: SellableUnit): string {
  const remaining = remainingInUnit(unit);
  if (remaining === null) return t("Stock unavailable");
  if (remaining <= 0) return t("Out of stock");

  return `${t("Available")}: ${String(remaining)}`;
}

function canAdd(unit: SellableUnit): boolean {
  const remaining = remainingInUnit(unit);

  return remaining === null || remaining > 0;
}

async function searchProducts(): Promise<void> {
  const filter = query.value.trim();
  if (filter.length < 2 || props.storeId === null) {
    results.value = [];
    loading.value = false;
    return;
  }

  loading.value = true;
  failed.value = false;
  const currentRequest = ++requestId;

  try {
    const response = await searchVariantsApi(filter, props.storeId);
    if (currentRequest !== requestId) return;

    const data = response.data?.data ?? [];
    results.value = Array.isArray(data) ? (data as VariantSearchResult[]) : [];
  } catch {
    if (currentRequest !== requestId) return;

    results.value = [];
    failed.value = true;
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000, group: "pos" });
  } finally {
    if (currentRequest === requestId) loading.value = false;
  }
}

function retrySearch(): void {
  void searchProducts();
}

function addUnit(unit: SellableUnit): void {
  if (!canAdd(unit)) return;

  const variant = unit.variant;
  emit("add", {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: variant.product?.name ?? "—",
    brand_name: variant.product?.brand?.name ?? null,
    base_unit_name: variant.product?.measurement_unit?.name ?? null,
    variant_identity: variant.option_values,
    variant_label: variant.option_values ?? unit.saleUnitName,
    sale_unit_id: unit.saleUnitId,
    quantity: 1,
    unit_price: unit.unitPrice,
    original_unit_price: unit.unitPrice,
    conversion_factor: unit.conversionFactor,
    line_total: unit.unitPrice,
    stock: variant.stock ?? null,
    minimum_stock_level: variant.minimum_stock_level ?? null,
    sale_units: variant.sale_units ?? [],
    sale_unit: unit.saleUnitId === null ? null : { id: unit.saleUnitId, name: unit.saleUnitName, conversion_factor: unit.conversionFactor },
  });
}

watch(query, (_value, _previousValue, onCleanup) => {
  if (debounceTimer) clearTimeout(debounceTimer);
  requestId++;
  results.value = [];
  failed.value = false;

  debounceTimer = setTimeout(() => {
    void searchProducts();
  }, 300);

  onCleanup(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
  });
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col" :aria-label="t('Products')">
    <div class="border-b border-surface-200 p-4 dark:border-surface-700">
      <label for="pos-product-search" class="mb-2 block text-lg font-semibold">{{ t("Find products") }}</label>
      <IconField>
        <InputIcon class="fa fa-search" />
        <InputText
          id="pos-product-search"
          v-model="query"
          :placeholder="t('Search by product or identifier')"
          :disabled="storeId === null"
          autocomplete="off"
          size="large"
          class="w-full"
          :aria-busy="loading"
        />
      </IconField>
      <small class="mt-2 block text-surface-500 dark:text-surface-400">{{ t("Enter at least 2 characters to search") }}</small>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto" aria-live="polite">
      <div v-if="showPrompt" class="flex min-h-64 flex-col items-center justify-center px-6 text-center text-surface-500 dark:text-surface-400">
        <i class="fa fa-magnifying-glass mb-3 text-3xl" aria-hidden="true" />
        <p class="m-0 font-medium">{{ t("Search the catalog") }}</p>
        <small>{{ t("Products matching your search will appear here") }}</small>
      </div>

      <div v-else-if="loading" class="flex flex-col gap-3 p-4" aria-label="Loading">
        <div v-for="index in 5" :key="index" class="h-20 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
      </div>

      <Message v-else-if="failed" severity="error" :closable="false" class="m-4">
        <div class="flex items-center justify-between gap-3">
          <span>{{ t("Product search failed") }}</span>
          <Button :label="t('Retry')" size="small" severity="danger" outlined @click="retrySearch" />
        </div>
      </Message>

      <div v-else-if="showEmpty" class="flex min-h-64 flex-col items-center justify-center px-6 text-center text-surface-500 dark:text-surface-400">
        <i class="fa fa-box-open mb-3 text-3xl" aria-hidden="true" />
        <p class="m-0 font-medium">{{ t("No products found") }}</p>
        <small>{{ t("Try another product name or identifier") }}</small>
      </div>

      <ul v-else class="divide-y divide-surface-200 dark:divide-surface-700">
        <li v-for="unit in units" :key="unit.key" class="p-4">
          <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="truncate font-semibold">{{ unit.variant.product?.name ?? "—" }}</span>
                <Badge v-if="unit.variant.option_values" :value="unit.variant.option_values" severity="secondary" />
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-surface-500 dark:text-surface-400">
                <span v-if="unit.variant.product?.brand?.name">{{ unit.variant.product.brand.name }}</span>
                <span>{{ unit.saleUnitName }}<template v-if="unit.conversionFactor !== 1"> · ×{{ unit.conversionFactor }}</template></span>
              </div>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="font-semibold tabular-nums">{{ formatCurrency(String(unit.unitPrice)) }}</span>
                <Tag :value="stockLabel(unit)" :severity="stockSeverity(unit)" rounded />
              </div>
            </div>
            <Button
              icon="fa fa-plus"
              :aria-label="`${t('Add')} ${unit.variant.product?.name ?? t('Product')}`"
              :disabled="!canAdd(unit)"
              raised
              class="min-h-11 min-w-11 shrink-0"
              @click="addUnit(unit)"
            />
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
