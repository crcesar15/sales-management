import { useAuthStore } from "@/Stores/auth";

export function useCurrencyFormatter() {
  const authStore = useAuthStore();

  const formatCurrency = (value: string) => {
    const currency = authStore.getSetting("finance", "currency");
    const decimalPrecision = parseInt(authStore.getSetting("currency", "decimal_precision") ?? "2");

    return `${currency} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  const formatCurrencySymbol = (value: string) => {
    const currencySymbol = authStore.getSetting("finance", "currency_symbol");
    const decimalPrecision = parseInt(authStore.getSetting("currency", "decimal_precision") ?? "2");

    return `${currencySymbol} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  return {
    formatCurrency,
    formatCurrencySymbol,
  };
}
