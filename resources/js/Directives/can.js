import { usePage } from "@inertiajs/vue3";

export default {
  mounted(el, binding) {
    const permissions = usePage().props.auth?.permissions || [];
    const { value } = binding;

    const hasPermission = Array.isArray(value)
      ? value.some((permission) => permissions.includes(permission))
      : permissions.includes(value);

    if (value !== true && !hasPermission) {
      el.remove(); // or el.style.display = 'none'
    }
  },
};
