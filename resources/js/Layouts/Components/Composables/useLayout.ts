import { ref, computed, reactive, readonly, watch } from "vue";

// ========================================
// Dark Mode Management
// ========================================
const isDarkMode = ref<boolean>(document.documentElement.classList.contains("app-dark"));

const setDarkMode = (enabled: boolean): void => {
  if (enabled === isDarkMode.value) return;

  if (!document.startViewTransition) {
    applyDarkMode(enabled);
    return;
  }

  document.startViewTransition(() => applyDarkMode(enabled));
};

const applyDarkMode = (enabled: boolean): void => {
  isDarkMode.value = enabled;
  document.documentElement.classList.toggle("app-dark", enabled);
};

const toggleDarkMode = (): void => {
  setDarkMode(!isDarkMode.value);
};

// ========================================
// Sidebar Collapse State
// ========================================
const SIDEBAR_KEY = "app-sidebar-collapsed";

const isSidebarCollapsed = ref<boolean>(localStorage.getItem(SIDEBAR_KEY) === "true");

// Persist changes
watch(isSidebarCollapsed, (newValue) => {
  localStorage.setItem(SIDEBAR_KEY, String(newValue));
});

const toggleSidebar = (): void => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// ========================================
// Layout State (Mobile Menu, etc.)
// ========================================
interface LayoutState {
  staticMenuDesktopInactive: boolean;
  overlayMenuActive: boolean;
  profileSidebarVisible: boolean;
  configSidebarVisible: boolean;
  staticMenuMobileActive: boolean;
  menuHoverActive: boolean;
  activeMenuItem: string | null;
}

const layoutState = reactive<LayoutState>({
  staticMenuDesktopInactive: false,
  overlayMenuActive: false,
  profileSidebarVisible: false,
  configSidebarVisible: false,
  staticMenuMobileActive: false,
  menuHoverActive: false,
  activeMenuItem: null,
});

const setActiveMenuItem = (item: { value?: string } | string): void => {
  layoutState.activeMenuItem = typeof item === "string" ? item : item.value || null;
};

const onMenuToggle = () => {
  // Simplified responsive: desktop = static, mobile = overlay
  if (window.innerWidth > 991) {
    layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
  } else {
    layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
  }
};

const resetMenu = () => {
  layoutState.overlayMenuActive = false;
  layoutState.staticMenuMobileActive = false;
  layoutState.menuHoverActive = false;
};

const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);

// ========================================
// Export Single Composable
// ========================================
export function useLayout() {
  return {
    // Dark mode
    isDarkMode,
    toggleDarkMode,
    setDarkMode,
    // Sidebar collapse
    isSidebarCollapsed,
    toggleSidebar,
    // Layout state
    layoutState: readonly(layoutState),
    onMenuToggle,
    isSidebarActive,
    setActiveMenuItem,
    resetMenu,
  };
}
