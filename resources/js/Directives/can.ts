import type { Directive, DirectiveBinding } from "vue";
import { watch } from "vue";
import { useAuthStore } from "@stores/auth";

type PermissionValue = string | string[] | true;

interface CanDirectiveElement extends HTMLElement {
  __canCleanup?: () => void;
  __canOriginalDisplay?: string;
}

const canDirective: Directive<CanDirectiveElement, PermissionValue> = {
  mounted(el: CanDirectiveElement, binding: DirectiveBinding<PermissionValue>) {
    const authStore = useAuthStore();

    const checkPermission = () => {
      const { value } = binding;

      // `v-can="true"` always shows the element
      if (value === true) {
        return;
      }

      const hasPermission = Array.isArray(value)
        ? authStore.canAny(value)
        : authStore.can(value);

      if (!hasPermission) {
        // Store original display value for potential restoration
        if (el.__canOriginalDisplay === undefined) {
          el.__canOriginalDisplay = el.style.display;
        }
        el.style.display = "none";
      } else {
        // Restore display if permission granted
        if (el.__canOriginalDisplay !== undefined) {
          el.style.display = el.__canOriginalDisplay;
        }
      }
    };

    // Initial check
    checkPermission();

    // Watch for permission changes (provides reactivity)
    const unwatch = watch(
      () => authStore.permissions,
      () => {
        checkPermission();
      },
      { deep: true }
    );

    // Store cleanup function for unmount
    el.__canCleanup = unwatch;
  },

  updated(el: CanDirectiveElement, binding: DirectiveBinding<PermissionValue>) {
    // Re-check if binding value changes
    const authStore = useAuthStore();
    const { value } = binding;

    if (value === true) {
      if (el.__canOriginalDisplay !== undefined) {
        el.style.display = el.__canOriginalDisplay;
      }
      return;
    }

    const hasPermission = Array.isArray(value)
      ? authStore.canAny(value)
      : authStore.can(value);

    if (!hasPermission) {
      if (el.__canOriginalDisplay === undefined) {
        el.__canOriginalDisplay = el.style.display;
      }
      el.style.display = "none";
    } else if (el.__canOriginalDisplay !== undefined) {
      el.style.display = el.__canOriginalDisplay;
    }
  },

  unmounted(el: CanDirectiveElement) {
    // Cleanup the watcher
    if (el.__canCleanup) {
      el.__canCleanup();
    }
  },
};

export default canDirective;
