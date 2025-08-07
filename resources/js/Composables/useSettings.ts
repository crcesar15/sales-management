import { usePage } from "@inertiajs/vue3";
import { SettingGrouped } from "../Types/setting-types";

export default function useSetting(group: string, key: string):string|null {
  const settings: SettingGrouped = usePage().props.settings;
  return settings?.[group]?.[key] ?? null;
}
