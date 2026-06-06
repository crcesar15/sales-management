import { ref } from "vue";

// Module-level state (shared across all component instances, same pattern as useLayout)
const isShiftBarVisible = ref(true);

export function usePosLayout() {
  const shiftBarHeight = 56; // Fixed height in pixels

  function hideShiftBar(): void {
    isShiftBarVisible.value = false;
  }

  function showShiftBar(): void {
    isShiftBarVisible.value = true;
  }

  return {
    isShiftBarVisible,
    shiftBarHeight,
    hideShiftBar,
    showShiftBar,
  };
}