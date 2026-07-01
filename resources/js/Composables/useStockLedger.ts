import { computed, type Ref } from "vue";
import type { SalesOrderLineItemForm } from "@/Types/sales-order-types";

type LineItem = SalesOrderLineItemForm;

/**
 * Per-variant live base-stock ledger.
 *
 * Maintains a running "remaining base stock" per product_variant_id by
 * subtracting the base units already allocated across ALL line items
 * (Σ quantity × conversion_factor) from the variant's static stock snapshot.
 *
 * Used by SOProductPicker (so the Available Tag in search results reflects
 * what's already in the order) and SOLineItemsTable (so each line's Available
 * and quantity max reflect sibling lines consuming the same base stock).
 *
 * Oversell is prevented before submit: when remainingBase < 0 for any variant,
 * hasOversell is true and the orchestrator blocks submit with an inline error.
 */
export function useStockLedger(lineItems: Ref<LineItem[]>) {
  /** Base-stock snapshot per variant, from search results (the source of truth for max stock). */
  const baseStockByVariant = computed<Map<number, number | null>>(() => {
    const map = new Map<number, number | null>();
    for (const item of lineItems.value) {
      if (!map.has(item.product_variant_id)) {
        map.set(item.product_variant_id, item.stock ?? null);
      }
    }
    return map;
  });

  /** Base units already allocated per variant, summed across all line items. */
  const allocatedBaseByVariant = computed<Map<number, number>>(() => {
    const map = new Map<number, number>();
    for (const item of lineItems.value) {
      const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
      const base = item.quantity * cf;
      map.set(item.product_variant_id, (map.get(item.product_variant_id) ?? 0) + base);
    }
    return map;
  });

  /** Remaining base stock per variant = snapshot − allocated. Null = unknown. */
  const remainingBaseByVariant = computed<Map<number, number | null>>(() => {
    const map = new Map<number, number | null>();
    for (const [variantId, snapshot] of baseStockByVariant.value) {
      if (snapshot === null) {
        map.set(variantId, null);
        continue;
      }
      const allocated = allocatedBaseByVariant.value.get(variantId) ?? 0;
      map.set(variantId, snapshot - allocated);
    }
    return map;
  });

  /** Resolver for the picker/table: returns remaining base for a variant, or null if unknown. */
  function getRemainingBase(variantId: number): number | null {
    return remainingBaseByVariant.value.get(variantId) ?? null;
  }

  /**
   * Remaining base stock for a variant, EXCLUDING the allocation of one line.
   * Used by a line's own Available Tag and quantity max so the number reflects
   * the ceiling for THAT line (what other lines have consumed), not a
   * self-referential recoil as the user edits the line's own quantity.
   */
  function getRemainingBaseExcludingLine(variantId: number, lineId: string): number | null {
    const snapshot = baseStockByVariant.value.get(variantId) ?? null;
    if (snapshot === null) return null;
    let allocated = 0;
    for (const item of lineItems.value) {
      if (item.product_variant_id !== variantId) continue;
      if (item.id === lineId) continue;
      const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
      allocated += item.quantity * cf;
    }
    return snapshot - allocated;
  }

  /** True if any variant has been over-allocated (remainingBase < 0). */
  const hasOversell = computed(() => {
    for (const remaining of remainingBaseByVariant.value.values()) {
      if (remaining !== null && remaining < 0) return true;
    }
    return false;
  });

  /** Variant ids that are currently over-allocated (for targeting error UI). */
  const oversoldVariantIds = computed<Set<number>>(() => {
    const set = new Set<number>();
    for (const [variantId, remaining] of remainingBaseByVariant.value) {
      if (remaining !== null && remaining < 0) set.add(variantId);
    }
    return set;
  });

  return {
    baseStockByVariant,
    allocatedBaseByVariant,
    remainingBaseByVariant,
    getRemainingBase,
    getRemainingBaseExcludingLine,
    hasOversell,
    oversoldVariantIds,
  };
}
