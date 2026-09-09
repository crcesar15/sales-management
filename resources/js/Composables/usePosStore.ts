import { computed, ref } from "vue";
import { defineStore } from "pinia";
import type { CashRegister, CashRegisterShift, PosCartItem, PosCustomer, PosDiscountType, PosSession, PosStore } from "@/Types/pos";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

function roundCurrency(amount: number): number {
  return Math.round((amount + Number.EPSILON) * 100) / 100;
}

export const usePosStore = defineStore("pos", () => {
  // ========== State ==========
  const store = ref<PosStore | null>(null);
  const register = ref<CashRegister | null>(null);
  const shift = ref<CashRegisterShift | null>(null);
  const userId = ref<number | null>(null);
  const cartItems = ref<PosCartItem[]>([]);
  const customer = ref<PosCustomer | null>(null);
  const isWalkIn = ref(false);
  const discountType = ref<PosDiscountType>("flat");
  const discountValue = ref(0);
  const taxRate = ref(0);
  const draftId = ref<number | null>(null);

  // ========== Getters ==========
  const isShiftOpen = computed(() => shift.value?.status === "open");
  const isCashier = computed(() => shift.value?.user_id === userId.value);
  const hasRegister = computed(() => register.value !== null);
  const hasShift = computed(() => shift.value !== null);
  const customerChoiceMade = computed(() => customer.value !== null || isWalkIn.value);
  const subTotal = computed(() => roundCurrency(cartItems.value.reduce((total, item) => total + item.line_total, 0)));
  const discountAmount = computed(() => {
    if (discountType.value === "percentage") {
      return roundCurrency(subTotal.value * (Math.min(Math.max(discountValue.value, 0), 100) / 100));
    }

    return Math.min(roundCurrency(Math.max(discountValue.value, 0)), subTotal.value);
  });
  const taxableAmount = computed(() => roundCurrency(subTotal.value - discountAmount.value));
  const taxAmount = computed(() => roundCurrency(taxableAmount.value * (taxRate.value / 100)));
  const total = computed(() => roundCurrency(taxableAmount.value + taxAmount.value));

  // ========== Actions ==========
  function setStore(data: PosStore): void {
    store.value = data;
  }

  function setRegister(data: CashRegister): void {
    register.value = data;
  }

  function setShift(data: CashRegisterShift | null): void {
    shift.value = data;
  }

  function setSession(data: PosSession): void {
    store.value = data.store;
    register.value = data.register;
    shift.value = data.shift;
  }

  function setUserId(id: number): void {
    userId.value = id;
  }

  function addCartItem(item: PosCartItem): boolean {
    const existingIndex = cartItems.value.findIndex(
      (cartItem) => cartItem.product_variant_id === item.product_variant_id && cartItem.sale_unit_id === item.sale_unit_id,
    );

    if (existingIndex === -1) {
      cartItems.value = [...cartItems.value, item];
      return true;
    }

    const existing = cartItems.value[existingIndex];
    const conversionFactor = existing.conversion_factor > 0 ? existing.conversion_factor : 1;
    const maximumQuantity = existing.stock === null || existing.stock === undefined ? null : Math.floor(existing.stock / conversionFactor);

    if (maximumQuantity !== null && existing.quantity >= maximumQuantity) {
      return false;
    }

    const updated = [...cartItems.value];
    updated[existingIndex] = {
      ...existing,
      quantity: existing.quantity + 1,
      line_total: roundCurrency((existing.quantity + 1) * existing.unit_price),
    };
    cartItems.value = updated;

    return true;
  }

  function setCartItems(items: PosCartItem[]): void {
    cartItems.value = items.map((item) => ({ ...item, line_total: roundCurrency(item.quantity * item.unit_price) }));
  }

  function selectCustomer(value: PosCustomer): void {
    customer.value = value;
    isWalkIn.value = false;
  }

  function selectWalkIn(): void {
    customer.value = null;
    isWalkIn.value = true;
  }

  function clearCustomer(): void {
    customer.value = null;
    isWalkIn.value = false;
  }

  function setDiscount(type: PosDiscountType, value: number | null): void {
    discountType.value = type;
    discountValue.value = Math.max(value ?? 0, 0);
  }

  function setTaxRate(value: number): void {
    taxRate.value = Math.min(Math.max(value, 0), 100);
  }

  function hydrateDraft(order: SalesOrderResponse): void {
    draftId.value = order.id;
    customer.value = order.customer?.id
      ? {
          id: order.customer.id,
          first_name: order.customer.first_name ?? "",
          last_name: order.customer.last_name ?? "",
          email: order.customer.email,
          phone: order.customer.phone,
          tax_id: order.customer.tax_id ?? "",
          tax_id_name: order.customer.tax_id_name ?? "",
        }
      : null;
    isWalkIn.value = order.customer_id === null;
    discountType.value = order.discount_type;
    discountValue.value = order.discount_value;
    cartItems.value = (order.items ?? []).map((item) => ({
      id: String(item.id),
      product_variant_id: item.product_variant_id,
      product_name: item.product_variant?.product?.name ?? "",
      brand_name: item.product_variant?.product?.brand?.name ?? null,
      base_unit_name: item.product_variant?.product?.measurement_unit?.name ?? null,
      variant_identity: item.product_variant?.option_values || item.product_variant?.identifier || null,
      variant_label: item.product_variant?.name ?? null,
      sale_unit_id: item.sale_unit_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      original_unit_price: item.unit_price,
      conversion_factor: item.conversion_factor,
      line_total: item.line_total,
      stock: item.stock ?? null,
      minimum_stock_level: item.product_variant?.minimum_stock_level ?? null,
      sale_units: item.product_variant?.sale_units ?? [],
      sale_unit: item.sale_unit,
    }));
  }

  function clearSale(): void {
    cartItems.value = [];
    clearCustomer();
    discountType.value = "flat";
    discountValue.value = 0;
    draftId.value = null;
  }

  function clearSession(): void {
    store.value = null;
    register.value = null;
    shift.value = null;
    clearSale();
  }

  return {
    // State
    store,
    register,
    shift,
    userId,
    cartItems,
    customer,
    isWalkIn,
    discountType,
    discountValue,
    taxRate,
    draftId,
    // Getters
    isShiftOpen,
    isCashier,
    hasRegister,
    hasShift,
    customerChoiceMade,
    subTotal,
    discountAmount,
    taxAmount,
    total,
    // Actions
    setStore,
    setRegister,
    setShift,
    setSession,
    setUserId,
    addCartItem,
    setCartItems,
    selectCustomer,
    selectWalkIn,
    clearCustomer,
    setDiscount,
    setTaxRate,
    hydrateDraft,
    clearSale,
    clearSession,
  };
});
