import { readonly, shallowRef, watch } from "vue";

// Module-level state (shared across all component instances, same pattern as useLayout)
const POS_SIDEBAR_KEY = "pos-sidebar-collapsed";
const isSidebarCollapsed = shallowRef(localStorage.getItem(POS_SIDEBAR_KEY) !== "false");
const isCashMovementDialogVisible = shallowRef(false);
const isMobileNavigationOpen = shallowRef(false);
const isCompactViewport = shallowRef(window.innerWidth < 1024);

watch(isSidebarCollapsed, (collapsed) => {
  localStorage.setItem(POS_SIDEBAR_KEY, String(collapsed));
});

export function usePosLayout() {
  function toggleNavigation(): void {
    if (isCompactViewport.value) {
      isMobileNavigationOpen.value = !isMobileNavigationOpen.value;
      return;
    }

    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  function closeMobileNavigation(): void {
    isMobileNavigationOpen.value = false;
  }

  function setCompactViewport(isCompact: boolean): void {
    isCompactViewport.value = isCompact;
    if (!isCompact) closeMobileNavigation();
  }

  function openCashMovementDialog(): void {
    isCashMovementDialogVisible.value = true;
  }

  function closeCashMovementDialog(): void {
    isCashMovementDialogVisible.value = false;
  }

  return {
    isSidebarCollapsed: readonly(isSidebarCollapsed),
    isMobileNavigationOpen: readonly(isMobileNavigationOpen),
    isCompactViewport: readonly(isCompactViewport),
    isCashMovementDialogVisible,
    toggleNavigation,
    closeMobileNavigation,
    setCompactViewport,
    openCashMovementDialog,
    closeCashMovementDialog,
  };
}
