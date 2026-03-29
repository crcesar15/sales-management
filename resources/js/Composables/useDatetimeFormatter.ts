import { useAuth } from "@/Composables/useAuth";
import moment from "moment-timezone";

export default function useDatetimeFormatter(datetime: string | null, format?: string) {
  const { getSetting } = useAuth();
  const timezone = getSetting("system", "timezone") ?? "UTC";
  if (!format) {
    format = getSetting("system", "datetime_format") ?? "YYYY-mm-dd HH:mm";
  }

  return moment(datetime).tz(timezone).format(format);
}
