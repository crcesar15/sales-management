<script setup lang="ts">
import { Button, InputNumber, Message, Select, SelectButton, Tag, useToast } from "primevue";
import { computed, shallowRef } from "vue";
import { router, Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { usePricingPreview } from "@composables/usePricingPreview";
import type { InventoryVariantDetail } from "@app-types/inventory-variant-types";
import PricingCostSummary from "./PricingCostSummary.vue";

const props = defineProps<{
  variant: InventoryVariantDetail;
  canEdit: boolean;
  formDirty: boolean;
  invalidTarget?: boolean;
  invalidPrice?: boolean;
  invalidSupplier?: boolean;
}>();
const catalogId = defineModel<number | null>("catalogId", { required: true });
const marginType = defineModel<"percent" | "amount">("marginType", { required: true });
const marginValue = defineModel<number | null>("marginValue", { required: true });
const price = defineModel<number>("price", { required: true });
const { t } = useI18n();
const toast = useToast();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();
const busy = shallowRef(false);
const pricing = computed(() => props.variant.pricing);
const offers = computed(() =>
  pricing.value.offers
    .filter((offer) => offer.eligible || offer.id === catalogId.value)
    .map((offer) => ({
      ...offer,
      label: `${offer.supplier_name} · ${offer.unit_name} · ${formatCurrency(String(offer.id === pricing.value.catalog_id ? (pricing.value.replacement_cost ?? offer.cost) : offer.cost))} / ${pricing.value.base_unit}`,
    })),
);
const selectedCost = computed(() =>
  catalogId.value === pricing.value.catalog_id
    ? pricing.value.replacement_cost
    : (pricing.value.offers.find((offer) => offer.id === catalogId.value)?.cost ?? null),
);
const marginTypes = computed(() => [
  { label: "%", value: "percent" },
  { label: currencyCode, value: "amount" },
]);
const { results, loading, failed } = usePricingPreview(() =>
  selectedCost.value === null || (marginType.value === "percent" && marginValue.value !== null && marginValue.value >= 100)
    ? []
    : [
        {
          product_variant_id: props.variant.id,
          price: selectedCost.value,
          margin_type: marginType.value,
          margin_value: marginValue.value,
          selling_price: price.value,
        },
      ],
);
const evaluation = computed(() => results.value[0] ?? null);
const money = (value: number | null | undefined) => (value == null ? "—" : formatCurrency(String(value)));

function applySuggestedPrice() {
  const suggestion = evaluation.value?.suggested_price;
  if (suggestion != null) price.value = suggestion;
}

function manage(action: "outdated" | "current" | "review") {
  if (pricing.value.reference_id === null) return;
  busy.value = true;
  router.put(
    route("inventory.pricing-reference.update", props.variant.id),
    { action, reference_id: pricing.value.reference_id },
    {
      preserveScroll: true,
      onError: (errors) => toast.add({ severity: "error", summary: t("Error"), detail: Object.values(errors)[0], life: 5000 }),
      onFinish: () => {
        busy.value = false;
      },
    },
  );
}
</script>

<template>
  <div class="flex flex-col gap-5">
    <PricingCostSummary v-if="variant.actual_costs" :costs="variant.actual_costs" :base-unit="pricing.base_unit" />
    <div class="flex flex-col gap-2">
      <label :for="`pricing-supplier-${variant.id}`">{{ t("Pricing supplier and purchase unit") }}</label>
      <Select
        :input-id="`pricing-supplier-${variant.id}`"
        v-model="catalogId"
        :options="offers"
        option-label="label"
        option-value="id"
        :disabled="!canEdit"
        :invalid="invalidSupplier"
        :placeholder="t('Select a pricing supplier')"
        fluid
      />
      <p v-if="!offers.length" class="m-0 text-sm text-surface-500 dark:text-surface-400">
        {{ t("Add a current supplier offer in the catalog to configure replacement pricing.") }}
      </p>
      <p class="m-0 text-sm text-surface-500 dark:text-surface-400">
        {{ t("Approving a purchase order selects that supplier. Separate PO discounts do not lower this reference.") }}
      </p>
    </div>
    <dl class="m-0 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-sm text-surface-500 dark:text-surface-400">{{ t("Replacement cost") }} / {{ pricing.base_unit }}</dt>
        <dd class="m-0 mt-1 text-lg font-semibold tabular-nums">{{ money(selectedCost) }}</dd>
      </div>
      <div>
        <dt class="text-sm text-surface-500 dark:text-surface-400">{{ t("Reference updated") }}</dt>
        <dd class="m-0 mt-1">{{ pricing.effective_at ? formatDatetime(pricing.effective_at) : "—" }}</dd>
      </div>
    </dl>
    <p v-if="pricing.change_amount !== null" class="m-0 text-sm tabular-nums">
      {{ t("Cost changes") }}: {{ money(pricing.change_amount) }}
      <span v-if="pricing.change_percent !== null">({{ pricing.change_percent }}%)</span>
    </p>
    <Message v-if="pricing.outdated" severity="warn" :closable="false">
      {{ t("Reference outdated. Verify the supplier cost before using the suggestion.") }}
    </Message>
    <p v-if="pricing.legacy_cost !== null && pricing.replacement_cost === null" class="m-0 text-sm text-surface-500 dark:text-surface-400">
      {{ t("Unverified legacy reference") }}: {{ money(pricing.legacy_cost) }}
    </p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="flex flex-col gap-2">
        <label for="pricing-target">{{ t("Target margin") }}</label>
        <div class="flex gap-2">
          <InputNumber
            input-id="pricing-target"
            v-model="marginValue"
            :min="0"
            :max="marginType === 'percent' ? 99.99 : undefined"
            :max-fraction-digits="2"
            :disabled="!canEdit"
            :invalid="invalidTarget"
            class="min-w-0 flex-1"
            fluid
          />
          <SelectButton
            v-model="marginType"
            :options="marginTypes"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            :disabled="!canEdit"
            :aria-label="t('Margin type')"
          />
        </div>
        <small>{{ t("Fixed profit amounts are per base unit. Percentage targets are gross margins.") }}</small>
      </div>
      <div class="flex flex-col gap-2">
        <label for="pricing-selling">{{ t("Selling Price") }}</label>
        <InputNumber
          input-id="pricing-selling"
          v-model="price"
          mode="currency"
          :currency="currencyCode"
          :min="0"
          :disabled="!canEdit"
          :invalid="invalidPrice"
          fluid
        />
      </div>
    </div>
    <div aria-live="polite" class="flex flex-col gap-3 border-y border-surface-200 py-4 dark:border-surface-700">
      <p v-if="loading" class="m-0 text-sm">{{ t("Calculating price impact...") }}</p>
      <Message v-else-if="failed" severity="error" :closable="false">
        {{ t("Price impact could not be calculated. Change a value to retry.") }}
      </Message>
      <template v-else-if="evaluation">
        <dl class="m-0 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <dt>{{ t("Current gross margin") }}</dt>
            <dd class="m-0 font-semibold tabular-nums">{{ evaluation.current_margin === null ? "—" : `${evaluation.current_margin}%` }}</dd>
          </div>
          <div>
            <dt>{{ t("Suggested selling price") }}</dt>
            <dd class="m-0 font-semibold tabular-nums">{{ money(evaluation.suggested_price) }}</dd>
          </div>
        </dl>
        <Message v-if="evaluation.below_cost" severity="error" :closable="false">{{ t("Below replacement cost") }}</Message>
        <Message v-else-if="evaluation.below_target" severity="warn" :closable="false">{{ t("Below target margin") }}</Message>
        <Button
          v-if="canEdit && evaluation.suggested_price !== null"
          type="button"
          :label="t('Apply suggested price')"
          icon="fa fa-check"
          outlined
          class="w-fit"
          @click="applySuggestedPrice"
        />
        <p class="m-0 text-sm text-surface-500 dark:text-surface-400">
          {{ t("Applying a suggestion updates this form. Save changes to publish the selling price.") }}
        </p>
      </template>
      <p v-else class="m-0 text-sm">{{ t("Select a supplier and set a target margin to calculate a suggestion.") }}</p>
    </div>
    <div v-if="canEdit && pricing.reference_id !== null" class="flex flex-wrap gap-2">
      <Button
        type="button"
        :label="t(pricing.outdated ? 'Mark reference current' : 'Mark reference outdated')"
        severity="secondary"
        outlined
        :disabled="formDirty || busy"
        @click="manage(pricing.outdated ? 'current' : 'outdated')"
      />
      <Button
        v-if="pricing.pending_review"
        type="button"
        :label="t('Acknowledge cost changes')"
        severity="secondary"
        outlined
        :disabled="formDirty || busy"
        @click="manage('review')"
      />
      <small v-if="formDirty">{{ t("Save or discard changes before managing the reference.") }}</small>
    </div>
    <slot name="before-history" />
    <section v-if="evaluation?.sale_units.length" class="flex flex-col gap-3" :aria-label="t('Selling unit review')">
      <h3 class="m-0 text-base font-semibold">{{ t("Selling unit review") }}</h3>
      <div
        v-for="unit in evaluation.sale_units"
        :key="unit.id"
        class="flex flex-wrap items-center justify-between gap-2 border-b border-surface-200 pb-2 dark:border-surface-700"
      >
        <span>{{ unit.name }} · {{ money(unit.price) }} · {{ t("Suggested") }} {{ money(unit.suggested_price) }}</span>
        <Tag
          v-if="unit.below_cost || unit.below_target"
          :severity="unit.below_cost ? 'danger' : 'warn'"
          :value="t(unit.below_cost ? 'Below replacement cost' : 'Below target margin')"
        />
      </div>
      <p class="m-0 text-sm text-surface-500 dark:text-surface-400">{{ t("Review independently priced packages in the Units tab.") }}</p>
    </section>
    <details>
      <summary class="cursor-pointer font-medium">{{ t("Replacement cost history") }}</summary>
      <ul class="mt-3 flex list-none flex-col gap-3 p-0">
        <li v-for="entry in pricing.history" :key="entry.id" class="flex flex-wrap items-center justify-between gap-2 text-sm">
          <span>
            {{ entry.date ? formatDatetime(entry.date) : "—" }} · {{ entry.supplier_name ?? t("Unverified legacy reference") }}
            <Tag v-if="entry.invalidated" :value="t('Cancelled')" severity="secondary" />
          </span>
          <span class="font-semibold tabular-nums">{{ money(entry.cost) }} / {{ pricing.base_unit }}</span>
          <Link
            v-if="entry.purchase_order_id"
            :href="route('purchase-orders.show', entry.purchase_order_id)"
            class="text-primary hover:underline"
          >
            {{ t("Purchase Order") }} #{{ entry.purchase_order_id }}
          </Link>
        </li>
      </ul>
      <p v-if="!pricing.history.length" class="text-sm">{{ t("No replacement cost history yet") }}</p>
    </details>
  </div>
</template>
