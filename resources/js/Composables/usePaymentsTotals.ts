import { computed, type ComputedRef, type Ref } from "vue";
import type { SalesOrderPaymentForm } from "@/Types/sales-order-types";

/**
 * Shared payment totals for an order. Single-sources the running total,
 * mismatch magnitude, and direction (short vs. over) so the payments panel
 * and the submit handlers cannot drift apart.
 */
export function usePaymentsTotals(
  payments: Ref<SalesOrderPaymentForm[]>,
  totalAmount: Ref<number> | ComputedRef<number>,
) {
  const paymentsTotal = computed(() => payments.value.reduce((sum, p) => sum + p.amount, 0));
  const paymentsDifference = computed(() => Math.abs(paymentsTotal.value - totalAmount.value));
  // positive shortfall = underpaid; negative = overpaid (operator owes change)
  const paymentsShortfall = computed(() => totalAmount.value - paymentsTotal.value);
  const isShortfall = computed(() => paymentsShortfall.value > 0);
  const isBalanced = computed(() => paymentsDifference.value <= 0.01);

  return { paymentsTotal, paymentsDifference, paymentsShortfall, isShortfall, isBalanced };
}