import { readonly, shallowRef, watch } from "vue";

// Module-level state (shared across all component instances, same pattern as useLayout)
const POS_SIDEBAR_KEY = "pos-sidebar-collapsed";
const isSidebarCollapsed = shallowRef(localStorage.getItem(POS_SIDEBAR_KEY) === "true");
const isCashMovementDialogVisible = shallowRef(false);

watch(isSidebarCollapsed, (collapsed) => {
  localStorage.setItem(POS_SIDEBAR_KEY, String(collapsed));
});

export function usePosLayout() {
  function toggleSidebar(): void {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  function openCashMovementDialog(): void {
    isCashMovementDialogVisible.value = true;
  }

  function closeCashMovementDialog(): void {
    isCashMovementDialogVisible.value = false;
  }

  return {
    isSidebarCollapsed: readonly(isSidebarCollapsed),
    isCashMovementDialogVisible,
    toggleSidebar,
    openCashMovementDialog,
    closeCashMovementDialog,
  };
}
