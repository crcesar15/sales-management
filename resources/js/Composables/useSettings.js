import { usePage } from "@inertiajs/vue3";

export default function useSetting(group, key) {
  const { settings } = usePage().props;
  return settings?.[group]?.[key] ?? null;
}
