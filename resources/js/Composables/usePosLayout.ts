import { readonly, shallowRef, watch } from "vue";

// Module-level state (shared across all component instances, same pattern as useLayout)
const POS_SIDEBAR_KEY = "pos-sidebar-collapsed";
const isShiftBarVisible = shallowRef(true);
const isSidebarCollapsed = shallowRef(localStorage.getItem(POS_SIDEBAR_KEY) === "true");

watch(isSidebarCollapsed, (collapsed) => {
  localStorage.setItem(POS_SIDEBAR_KEY, String(collapsed));
});

export function usePosLayout() {
  const shiftBarHeight = 56; // Fixed height in pixels

  function hideShiftBar(): void {
    isShiftBarVisible.value = false;
  }

  function showShiftBar(): void {
    isShiftBarVisible.value = true;
  }

  function toggleSidebar(): void {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  return {
    isShiftBarVisible: readonly(isShiftBarVisible),
    isSidebarCollapsed: readonly(isSidebarCollapsed),
    shiftBarHeight,
    hideShiftBar,
    showShiftBar,
    toggleSidebar,
  };
}
