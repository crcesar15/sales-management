import { computed, shallowRef } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import { route } from "ziggy-js";
import i18n from "@/app";
import { useAuth } from "@composables/useAuth";
import { useLayout } from "@layouts/Components/Composables/useLayout";
import type { UserLocale, UserTheme } from "@app-types/user-types";

const LEGACY_DARK_MODE_KEY = "app-dark-mode";
const defaultLocale: UserLocale = import.meta.env.VITE_APP_LOCALE === "en" ? "en" : "es";
const locale = shallowRef<UserLocale>(document.documentElement.lang === "en" ? "en" : document.documentElement.lang === "es" ? "es" : defaultLocale);
let isMigratingLegacyTheme = false;

export function useUserPreferences() {
  const toast = useToast();
  const { user } = useAuth();
  const { isDarkMode, setDarkMode } = useLayout();
  const theme = computed<UserTheme>(() => (isDarkMode.value ? "dark" : "light"));

  function setLocale(value: UserLocale): void {
    locale.value = value;
    i18n.global.locale.value = value;
    document.documentElement.lang = value;
  }

  function save(preferences: Partial<{ locale: UserLocale; theme: UserTheme }>, rollback: () => void, onSuccess?: () => void): void {
    router.patch(route("user-preferences.update"), preferences, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => onSuccess?.(),
      onError: () => {
        rollback();
        toast.add({ severity: "error", summary: i18n.global.t("Error"), detail: i18n.global.t("Unable to save preferences"), life: 3000 });
      },
    });
  }

  function updateLocale(value: UserLocale): void {
    if (value === locale.value) return;

    const previousLocale = locale.value;
    setLocale(value);
    save({ locale: value }, () => setLocale(previousLocale));
  }

  function updateTheme(value: UserTheme): void {
    if (value === theme.value) return;

    const previousTheme = theme.value;
    setDarkMode(value === "dark");
    save({ theme: value }, () => setDarkMode(previousTheme === "dark"));
  }

  function migrateLegacyThemePreference(): void {
    if (isMigratingLegacyTheme || user.value?.theme === "light" || user.value?.theme === "dark") return;

    const legacyTheme = localStorage.getItem(LEGACY_DARK_MODE_KEY);

    if (legacyTheme !== "true" && legacyTheme !== "false") return;

    isMigratingLegacyTheme = true;
    const value: UserTheme = legacyTheme === "true" ? "dark" : "light";
    const previousTheme = theme.value;
    setDarkMode(value === "dark");

    save(
      { theme: value },
      () => {
        setDarkMode(previousTheme === "dark");
        isMigratingLegacyTheme = false;
      },
      () => {
        localStorage.removeItem(LEGACY_DARK_MODE_KEY);
        isMigratingLegacyTheme = false;
      },
    );
  }

  return {
    locale,
    theme,
    updateLocale,
    updateTheme,
    migrateLegacyThemePreference,
  };
}
