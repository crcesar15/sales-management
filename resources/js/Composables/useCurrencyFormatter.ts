import useSetting from "./useSettings";

export function useCurrencyFormatter() {

  const formatCurrency = (value: string) => {
    const currency = useSetting("finance", "currency");
    const decimalPrecision = parseInt(useSetting("currency", "decimal_precision") ?? '2');

    return `${currency} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  const formatCurrencySymbol = (value: string) => {
    const currencySymbol = useSetting("finance", "currency_symbol");
    const decimalPrecision = parseInt(useSetting("currency", "decimal_precision") ?? '2');

    return `${currencySymbol} ${parseFloat(value).toFixed(decimalPrecision)}`;
  };

  return {
    formatCurrency,
    formatCurrencySymbol,
  };
}
