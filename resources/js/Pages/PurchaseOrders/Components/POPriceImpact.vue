<script setup lang="ts">
import { Message, Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PricingPreview } from "@app-types/pricing-types";

defineProps<{ impact: PricingPreview | null; actualBaseCost: number | null; loading: boolean; failed: boolean }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const money = (value: number | null) => (value === null ? "—" : formatCurrency(String(value)));
const difference = (cost: number, previous: number) =>
  `${cost - previous >= 0 ? "+" : ""}${(((cost - previous) / previous) * 100).toFixed(2)}%`;
</script>

<template>
  <section
    class="mt-3 flex flex-col gap-2 border-t border-surface-200 pt-3 text-sm dark:border-surface-700"
    :aria-label="t('Price impact preview')"
    aria-live="polite"
  >
    <p v-if="loading" class="m-0">{{ t("Calculating price impact...") }}</p>
    <Message v-else-if="failed" severity="warn" :closable="false">
      {{ t("Price impact could not be calculated. Change a value to retry.") }}
    </Message>
    <template v-else-if="impact">
      <dl class="m-0 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div>
          <dt>{{ t("Actual base-unit cost after all discounts") }}</dt>
          <dd class="m-0 font-semibold tabular-nums">{{ money(actualBaseCost) }}</dd>
        </div>
        <div>
          <dt>{{ t("Normal cost per base unit") }}</dt>
          <dd class="m-0 font-semibold tabular-nums">{{ money(impact.cost) }}</dd>
        </div>
        <div>
          <dt>{{ t("Previous reference") }}</dt>
          <dd class="m-0 tabular-nums">
            {{ money(impact.previous_cost) }}
            <span v-if="impact.previous_cost && impact.cost !== null">({{ difference(impact.cost, impact.previous_cost) }})</span>
          </dd>
        </div>
        <div>
          <dt>{{ t("Current gross margin") }}</dt>
          <dd class="m-0 tabular-nums">{{ impact.current_margin === null ? "—" : `${impact.current_margin}%` }}</dd>
        </div>
        <div>
          <dt>{{ t("Selling Price") }}</dt>
          <dd class="m-0 tabular-nums">{{ money(impact.price) }}</dd>
        </div>
        <div>
          <dt>{{ t("Suggested selling price") }}</dt>
          <dd class="m-0 font-semibold tabular-nums">{{ money(impact.suggested_price) }}</dd>
        </div>
      </dl>
      <div class="flex flex-wrap gap-2">
        <Tag v-if="impact.below_cost" severity="danger" :value="t('Below replacement cost')" />
        <Tag v-else-if="impact.below_target" severity="warn" :value="t('Below target margin')" />
        <template v-for="unit in impact.sale_units" :key="unit.id">
          <Tag
            v-if="unit.below_cost || unit.below_target"
            :severity="unit.below_cost ? 'danger' : 'warn'"
            :value="`${unit.name}: ${t(unit.below_cost ? 'Below replacement cost' : 'Below target margin')}`"
          />
        </template>
      </div>
      <p class="m-0 text-surface-500 dark:text-surface-400">
        {{ t("Preview only. Approval updates the supplier reference and alerts; selling prices stay unchanged.") }}
      </p>
    </template>
  </section>
</template>
