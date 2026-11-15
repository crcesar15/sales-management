<script setup lang="ts">
import { Button, Paginator, Select, Tag, useToast } from "primevue";
import { computed, shallowRef } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useAuth } from "@composables/useAuth";
import type { PriceReviewCollection, PriceReviewAlert } from "@app-types/stock-alert-types";

defineProps<{ alerts: PriceReviewCollection; reason: string | null; severity: string | null; reviewState: string }>();
const emit = defineEmits<{
  page: [page: number, perPage: number];
  filter: [reason: string | null];
  severity: [severity: string | null];
  state: [state: string];
}>();
const { t } = useI18n();
const { can } = useAuth();
const { formatCurrency } = useCurrencyFormatter();
const toast = useToast();
const reviewing = shallowRef<number | null>(null);
const filters = computed(() => [
  { label: t("All price reviews"), value: null },
  { label: t("Pricing risks"), value: "pricing" },
  { label: t("Cost changes"), value: "changes" },
  { label: t("Setup and freshness"), value: "setup" },
]);
const money = (value: number | null) => (value === null ? "—" : formatCurrency(String(value)));
const severities = computed(() => [
  { label: t("All severities"), value: null },
  { label: t("Below replacement cost"), value: "critical" },
  { label: t("Other reviews"), value: "other" },
]);
const states = computed(() => [
  { label: t("Active reviews"), value: "active" },
  { label: t("Reviewed changes"), value: "reviewed" },
]);
function acknowledge(item: PriceReviewAlert) {
  reviewing.value = item.id;
  router.put(
    route("inventory.pricing-reference.update", item.id),
    { action: "review", reference_id: item.pricing.reference_id },
    {
      preserveScroll: true,
      onError: (errors) => toast.add({ severity: "error", summary: t("Error"), detail: Object.values(errors)[0], life: 5000 }),
      onFinish: () => {
        reviewing.value = null;
      },
    },
  );
}
</script>

<template>
  <div class="flex flex-col gap-4 p-4">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="flex flex-col gap-2">
        <label for="price-review-filter">{{ t("Review reason") }}</label>
        <Select
          input-id="price-review-filter"
          :placeholder="t('All price reviews')"
          :model-value="reason"
          :options="filters"
          option-label="label"
          option-value="value"
          @update:model-value="emit('filter', $event)"
        />
      </div>
      <div class="flex flex-col gap-2">
        <label for="price-review-severity">{{ t("Severity") }}</label>
        <Select
          input-id="price-review-severity"
          :model-value="severity"
          :placeholder="t('All severities')"
          :options="severities"
          option-label="label"
          option-value="value"
          @update:model-value="emit('severity', $event)"
        />
      </div>
      <div class="flex flex-col gap-2">
        <label for="price-review-state">{{ t("Review status") }}</label>
        <Select
          input-id="price-review-state"
          :model-value="reviewState"
          :options="states"
          option-label="label"
          option-value="value"
          @update:model-value="emit('state', $event)"
        />
      </div>
    </div>
    <p class="m-0 text-sm text-surface-500 dark:text-surface-400">
      {{ t("Acknowledging a cost change does not clear unresolved pricing risks. Prices are shared across stores.") }}
    </p>
    <p v-if="!alerts.data.length" class="m-0 py-8 text-center">{{ t("No price reviews match these filters") }}</p>
    <article
      v-for="item in alerts.data"
      :key="item.id"
      class="flex flex-col gap-4 border-t border-surface-200 pt-4 dark:border-surface-700"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <Link
            :href="route('inventory.variants.show', { variant: item.id, tab: 'pricing' })"
            class="font-semibold text-primary hover:underline"
          >
            {{ item.product_name }}
            <span v-if="item.name">· {{ item.name }}</span>
          </Link>
          <p class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400">
            {{ item.pricing.supplier_name ?? t("Select a pricing supplier") }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Tag v-if="reviewState === 'reviewed'" :value="t('Reviewed changes')" severity="secondary" />
          <Tag
            v-for="itemReason in item.pricing.reasons"
            :key="itemReason"
            :value="t(itemReason)"
            :severity="itemReason === 'Below replacement cost' ? 'danger' : itemReason === 'Cost decreased' ? 'info' : 'warn'"
          />
        </div>
      </div>
      <dl class="m-0 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div>
          <dt class="text-sm">{{ t("Replacement cost") }} / {{ item.pricing.base_unit }}</dt>
          <dd class="m-0 font-semibold tabular-nums">{{ money(item.pricing.replacement_cost) }}</dd>
        </div>
        <div>
          <dt class="text-sm">{{ t("Previous reference") }}</dt>
          <dd class="m-0 tabular-nums">{{ money(item.pricing.previous_cost) }}</dd>
        </div>
        <div>
          <dt class="text-sm">{{ t("Selling Price") }}</dt>
          <dd class="m-0 tabular-nums">{{ money(item.price) }}</dd>
        </div>
        <div>
          <dt class="text-sm">{{ t("Suggested selling price") }}</dt>
          <dd class="m-0 font-semibold tabular-nums">{{ money(item.pricing.evaluation.suggested_price) }}</dd>
        </div>
      </dl>
      <p class="m-0 text-sm tabular-nums">
        {{ t("Target margin") }}:
        {{
          item.pricing.target_value === null
            ? "—"
            : item.pricing.target_type === "percent"
              ? `${item.pricing.target_value}%`
              : money(item.pricing.target_value)
        }}
        · {{ t("Current gross margin") }}:
        {{ item.pricing.evaluation.current_margin === null ? "—" : `${item.pricing.evaluation.current_margin}%` }}
        <span v-if="item.pricing.change_amount !== null">
          · {{ t("Cost changes") }}: {{ money(item.pricing.change_amount) }}
          <span v-if="item.pricing.change_percent !== null">({{ item.pricing.change_percent }}%)</span>
        </span>
      </p>
      <template v-for="unit in item.pricing.sale_units" :key="unit.id">
        <p v-if="unit.below_cost || unit.below_target" class="m-0 text-sm">
          {{ unit.name }}: {{ money(unit.price) }} · {{ t("Suggested") }} {{ money(unit.suggested_price) }} ·
          {{ t(unit.below_cost ? "Below replacement cost" : "Below target margin") }}
        </p>
      </template>
      <div class="flex flex-wrap gap-2">
        <Link
          :href="route('inventory.variants.show', { variant: item.id, tab: 'pricing' })"
          class="inline-flex min-h-10 items-center font-medium text-primary hover:underline"
        >
          {{ t("Review pricing") }}
          <i class="fa fa-arrow-right ml-2" aria-hidden="true" />
        </Link>
        <Button
          v-if="can('inventory.edit') && item.pricing.pending_review"
          :label="t('Acknowledge cost changes')"
          severity="secondary"
          outlined
          :loading="reviewing === item.id"
          :disabled="reviewing !== null"
          @click="acknowledge(item)"
        />
      </div>
    </article>
    <Paginator
      :rows="alerts.meta.per_page"
      :first="(alerts.meta.current_page - 1) * alerts.meta.per_page"
      :total-records="alerts.meta.total"
      :rows-per-page-options="[10, 20, 50]"
      :page-link-size="3"
      @page="emit('page', $event.page + 1, $event.rows)"
    />
  </div>
</template>
