import { ref, computed, reactive, readonly, watch } from "vue";

// ========================================
// Dark Mode Management
// ========================================
const DARK_MODE_KEY = "app-dark-mode";

const isDarkMode = ref<boolean>(localStorage.getItem(DARK_MODE_KEY) === "true");

// Apply initial state
if (isDarkMode.value) {
  document.documentElement.classList.add("app-dark");
}

// Persist changes
watch(isDarkMode, (newValue) => {
  localStorage.setItem(DARK_MODE_KEY, String(newValue));
});

const toggleDarkMode = (): void => {
  if (!document.startViewTransition) {
    executeDarkModeToggle();
    return;
  }
  document.startViewTransition(() => executeDarkModeToggle());
};

const executeDarkModeToggle = (): void => {
  isDarkMode.value = !isDarkMode.value;
  document.documentElement.classList.toggle("app-dark");
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
