import { useAuthStore } from "@/Stores/auth";
import moment from "moment-timezone";

export default function useDatetimeFormatter(datetime: string|null, format?: string) {
  const authStore = useAuthStore();
  const timezone = authStore.getSetting("system", "timezone") ?? "UTC";
  if (!format) {
    format = authStore.getSetting("system", "datetime_format") ?? "YYYY-mm-dd HH:mm";
  }

  return moment(datetime).tz(timezone).format(format);
}
