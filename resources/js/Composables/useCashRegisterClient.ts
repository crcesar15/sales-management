import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type InertiaData = any;

export function useCashRegisterClient() {
  // ── Register mutations ──

  const storeRegister = (values: InertiaData) => {
    return router.post(route("cash-registers.store"), values, { preserveScroll: true });
  };

  const updateRegister = (id: number, values: InertiaData) => {
    return router.put(route("cash-registers.update", id), values, { preserveScroll: true });
  };

  const deleteRegister = (id: number) => {
    return router.delete(route("cash-registers.destroy", id), { preserveScroll: true });
  };

  // ── Shift mutations ──

  const openShift = (values: InertiaData) => {
    return router.post(route("shifts.open"), values, { preserveScroll: true });
  };

  const closeShift = (id: number, values: InertiaData) => {
    return router.patch(route("shifts.close", id), values, { preserveScroll: true });
  };

  const forceCloseShift = (id: number, values: InertiaData) => {
    return router.patch(route("shifts.force-close", id), values, { preserveScroll: true });
  };

  const addMovement = (shiftId: number, values: InertiaData) => {
    return router.post(route("shifts.movements.store", shiftId), values, { preserveScroll: true });
  };

  return {
    storeRegister,
    updateRegister,
    deleteRegister,
    openShift,
    closeShift,
    forceCloseShift,
    addMovement,
  };
}
