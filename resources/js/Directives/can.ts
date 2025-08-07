import { usePage } from "@inertiajs/vue3";

interface Binding {
  value: string[] | string | true // [edit-roles, update-roles] or 'edit-roles'
}

export default {
  mounted(el:HTMLElement, binding: Binding) {
    const permissions = usePage().props.auth?.permissions || [];
    const { value } = binding;

    if (value !== true) {
      const hasPermission = Array.isArray(value)
        ? value.some((permission) => permissions.includes(permission))
        : permissions.includes(value);

      if (!hasPermission) {
        el.remove(); // or el.style.display = 'none'
      }
    }
  },
};
