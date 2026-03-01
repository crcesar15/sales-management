import { computed, reactive, readonly } from "vue";

interface LayoutConfig {
  preset: string;
  primary: string;
  surface: string | null;
  darkTheme: boolean;
  menuMode: "static" | "overlay";
}

interface LayoutState {
  staticMenuDesktopInactive: boolean;
  overlayMenuActive: boolean;
  profileSidebarVisible: boolean;
  configSidebarVisible: boolean;
  staticMenuMobileActive: boolean;
  menuHoverActive: boolean;
  activeMenuItem: string | null;
  sidebarCollapsed: boolean;
}

const layoutConfig = reactive<LayoutConfig>({
  preset: "Aura",
  primary: "emerald",
  surface: null,
  darkTheme: false,
  menuMode: "static",
});

const layoutState = reactive<LayoutState>({
  staticMenuDesktopInactive: false,
  overlayMenuActive: false,
  profileSidebarVisible: false,
  configSidebarVisible: false,
  staticMenuMobileActive: false,
  menuHoverActive: false,
  activeMenuItem: null,
  sidebarCollapsed: false,
});

export function useLayout() {
  const setPrimary = (value: string): void => {
    layoutConfig.primary = value;
  };

  const setSurface = (value: string | null): void => {
    layoutConfig.surface = value;
  };

  const setPreset = (value: string): void => {
    layoutConfig.preset = value;
  };

  const setActiveMenuItem = (item: { value?: string } | string): void => {
    layoutState.activeMenuItem = typeof item === "string" ? item : (item.value || null);
  };

  const setMenuMode = (mode: "static" | "overlay"): void => {
    layoutConfig.menuMode = mode;
  };

  const toggleDarkMode = (): void => {
    if (!document.startViewTransition) {
      executeDarkModeToggle();

      return;
    }

    document.startViewTransition(() => executeDarkModeToggle());
  };

  const executeDarkModeToggle = (): void => {
    layoutConfig.darkTheme = !layoutConfig.darkTheme;
    document.documentElement.classList.toggle("app-dark");
  };

  const onMenuToggle = () => {
    if (layoutConfig.menuMode === "overlay") {
      layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
    }

    if (window.innerWidth > 991) {
      layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
    } else {
      layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
    }
  };

  const onSidebarCollapse = (): void => {
    layoutState.sidebarCollapsed = !layoutState.sidebarCollapsed;
  };

  const resetMenu = () => {
    layoutState.overlayMenuActive = false;
    layoutState.staticMenuMobileActive = false;
    layoutState.menuHoverActive = false;
  };

  const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);

  const isDarkTheme = computed(() => layoutConfig.darkTheme);

  const getPrimary = computed(() => layoutConfig.primary);

  const getSurface = computed(() => layoutConfig.surface);

  const isSidebarCollapsed = computed(() => layoutState.sidebarCollapsed);

  return {
    layoutConfig: readonly(layoutConfig),
    layoutState: readonly(layoutState),
    onMenuToggle,
    onSidebarCollapse,
    isSidebarActive,
    isSidebarCollapsed,
    isDarkTheme,
    getPrimary,
    getSurface,
    setActiveMenuItem,
    toggleDarkMode,
    setPrimary,
    setSurface,
    setPreset,
    resetMenu,
    setMenuMode,
  };
}
