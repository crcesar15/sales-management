import { useAuth } from "@/Composables/useAuth";

export function useCurrencyFormatter() {
  const { getSetting } = useAuth();

  const formatCurrency = (value: string) => {
    const currency = getSetting("finance", "currency");
    const decimalPrecision = parseInt(getSetting("currency", "decimal_precision") ?? "2");

    return `${currency} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  const formatCurrencySymbol = (value: string) => {
    const currencySymbol = getSetting("finance", "currency_symbol");
    const decimalPrecision = parseInt(getSetting("currency", "decimal_precision") ?? "2");

    return `${currencySymbol} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  return {
    formatCurrency,
    formatCurrencySymbol,
  };
}
