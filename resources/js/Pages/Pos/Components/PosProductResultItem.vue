<script setup lang="ts">
import { Badge, Button, Tag } from "primevue";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { usePosUnitStock } from "@composables/usePosUnitStock";
import type { PosProductView } from "@composables/usePosProductView";
import type { PosSellableUnit } from "@/Types/pos";

const props = defineProps<{
  unit: PosSellableUnit;
  view: PosProductView;
  getRemainingBase: (variantId: number) => number | null;
}>();

const emit = defineEmits<{
  (e: "add", unit: PosSellableUnit): void;
  (e: "info", event: Event, unit: PosSellableUnit): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { stockSeverity, stockLabel, canAdd } = usePosUnitStock((variantId) => props.getRemainingBase(variantId));

const imageUrl = computed(() => props.unit.variant.thumbnail_url ?? null);
const imageFailed = ref(false);
const showImage = computed(() => imageUrl.value !== null && !imageFailed.value);
const productName = computed(() => props.unit.variant.product?.name ?? "—");
const imageInitials = computed(() => (props.unit.variant.product?.name ?? "").trim().slice(0, 2).toUpperCase() || "—");

function addUnit(): void {
  emit("add", props.unit);
}

function showInfo(event: Event): void {
  emit("info", event, props.unit);
}
</script>

<template>
  <article
    v-if="view === 'grid'"
    class="flex h-full flex-col overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
  >
    <div class="flex aspect-square shrink-0 items-center justify-center bg-surface-100 dark:bg-surface-800">
      <img
        v-if="showImage"
        :src="imageUrl ?? ''"
        :alt="t('Product image')"
        loading="lazy"
        decoding="async"
        class="h-full w-full object-contain p-1"
        @error="imageFailed = true"
      />
      <span v-else class="text-5xl font-bold text-surface-400 dark:text-surface-500" aria-hidden="true">{{ imageInitials }}</span>
    </div>
    <div class="flex min-w-0 flex-1 flex-col gap-1.5 p-3">
      <button
        v-tooltip.top="t('Product Details')"
        type="button"
        class="pos-result-item__name line-clamp-2 text-left text-sm font-bold leading-tight"
        :aria-label="`${t('Product Details')}: ${productName}`"
        @click="showInfo"
      >
        {{ productName }}
      </button>
      <Badge
        v-if="unit.variant.option_values"
        :value="unit.variant.option_values"
        class="w-fit max-w-full truncate text-xs"
      />
      <span v-if="unit.variant.product?.brand?.name" class="truncate text-xs text-surface-600 dark:text-surface-200">
        {{ unit.variant.product.brand.name }}
      </span>
      <span class="truncate text-xs text-surface-600 dark:text-surface-200">
        {{ unit.saleUnitName }}
        <template v-if="unit.conversionFactor !== 1">
          = {{ unit.conversionFactor }} {{ unit.variant.product?.measurement_unit?.name ?? t("Unit") }}
        </template>
      </span>
      <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 pt-1">
        <span class="text-sm font-semibold tabular-nums">{{ formatCurrency(String(unit.unitPrice)) }}</span>
        <Tag :value="stockLabel(unit)" :severity="stockSeverity(unit)" rounded class="text-xs" />
      </div>
      <Button
        icon="fa fa-plus"
        :aria-label="`${t('Add')} ${productName}`"
        :disabled="!canAdd(unit)"
        raised
        class="min-h-11 w-full"
        @click="addUnit"
      />
    </div>
  </article>

  <div v-else class="flex items-start gap-3">
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-tooltip.top="t('Product Details')"
          type="button"
          class="pos-result-item__name text-left text-lg font-bold"
          :aria-label="`${t('Product Details')}: ${productName}`"
          @click="showInfo"
        >
          {{ productName }}
        </button>
        <Badge v-if="unit.variant.option_values" :value="unit.variant.option_values" severity="primary" />
      </div>
      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-surface-600 dark:text-surface-200">
        <span v-if="unit.variant.product?.brand?.name">{{ unit.variant.product.brand.name }}</span>
        <span>|</span>
        <span>
          {{ unit.saleUnitName }}
          <template v-if="unit.conversionFactor !== 1">
            = {{ unit.conversionFactor }} {{ unit.variant.product?.measurement_unit?.name ?? t("Unit") }}
          </template>
        </span>
      </div>
      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="font-semibold tabular-nums">{{ formatCurrency(String(unit.unitPrice)) }}</span>
        <Tag :value="stockLabel(unit)" :severity="stockSeverity(unit)" rounded />
      </div>
    </div>
    <Button
      icon="fa fa-plus"
      :aria-label="`${t('Add')} ${productName}`"
      :disabled="!canAdd(unit)"
      raised
      class="min-h-11 min-w-11 shrink-0"
      @click="addUnit"
    />
  </div>
</template>

<style scoped>
.pos-result-item__name {
  padding: 0;
  color: inherit;
  text-align: left;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.pos-result-item__name:hover,
.pos-result-item__name:focus-visible {
  color: var(--p-primary-color);
  text-decoration: underline;
}

.pos-result-item__name:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}
</style>