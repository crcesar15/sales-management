import { useI18n } from "vue-i18n";
import type { PosSellableUnit } from "@/Types/pos";

export function usePosUnitStock(getRemainingBase: (variantId: number) => number | null) {
  const { t } = useI18n();

  function remainingInUnit(unit: PosSellableUnit): number | null {
    const remainingBase = getRemainingBase(unit.variant.id) ?? unit.variant.stock ?? null;
    if (remainingBase === null) return null;

    return Math.floor(remainingBase / unit.conversionFactor);
  }

  function stockSeverity(unit: PosSellableUnit): "success" | "warn" | "danger" {
    const remaining = remainingInUnit(unit);
    if (remaining === null || remaining > (unit.variant.minimum_stock_level ?? 0)) return "success";
    if (remaining <= 0) return "danger";

    return "warn";
  }

  function stockLabel(unit: PosSellableUnit): string {
    const remaining = remainingInUnit(unit);
    if (remaining === null) return t("Stock unavailable");
    if (remaining <= 0) return t("Out of stock");

    return `${t("Available")}: ${String(remaining)}`;
  }

  function canAdd(unit: PosSellableUnit): boolean {
    const remaining = remainingInUnit(unit);

    return remaining === null || remaining > 0;
  }

  return { remainingInUnit, stockSeverity, stockLabel, canAdd };
}