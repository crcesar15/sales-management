import { computed, ref } from "vue";
import { defineStore } from "pinia";
import type { CashRegister, CashRegisterShift, PosCartItem, PosCustomer, PosDiscountType, PosStore } from "@/Types/pos";

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
  const discountType = ref<PosDiscountType>("amount");
  const discountValue = ref(0);

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
  const total = computed(() => roundCurrency(subTotal.value - discountAmount.value));

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

  function clearSale(): void {
    cartItems.value = [];
    clearCustomer();
    discountType.value = "amount";
    discountValue.value = 0;
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
    // Getters
    isShiftOpen,
    isCashier,
    hasRegister,
    hasShift,
    customerChoiceMade,
    subTotal,
    discountAmount,
    total,
    // Actions
    setStore,
    setRegister,
    setShift,
    setUserId,
    addCartItem,
    setCartItems,
    selectCustomer,
    selectWalkIn,
    clearCustomer,
    setDiscount,
    clearSale,
    clearSession,
  };
});
