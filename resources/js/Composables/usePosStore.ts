import { computed, ref } from "vue";
import { defineStore } from "pinia";
import type { CashRegister, CashRegisterShift } from "@/Types/pos";

interface StoreInfo {
  id: number;
  name: string;
}

export const usePosStore = defineStore("pos", () => {
  // ========== State ==========
  const store = ref<StoreInfo | null>(null);
  const register = ref<CashRegister | null>(null);
  const shift = ref<CashRegisterShift | null>(null);
  const userId = ref<number | null>(null);

  // ========== Getters ==========
  const isShiftOpen = computed(() => shift.value?.status === "open");
  const isCashier = computed(() => shift.value?.cashier_id === userId.value);
  const hasRegister = computed(() => register.value !== null);
  const hasShift = computed(() => shift.value !== null);

  // ========== Actions ==========
  function setStore(data: StoreInfo): void {
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

  function clearSession(): void {
    store.value = null;
    register.value = null;
    shift.value = null;
  }

  return {
    // State
    store,
    register,
    shift,
    userId,
    // Getters
    isShiftOpen,
    isCashier,
    hasRegister,
    hasShift,
    // Actions
    setStore,
    setRegister,
    setShift,
    setUserId,
    clearSession,
  };
});