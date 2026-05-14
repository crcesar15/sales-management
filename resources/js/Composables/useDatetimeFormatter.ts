import { useAuth } from "@/Composables/useAuth";
import moment from "moment-timezone";

export default function useDatetimeFormatter(datetime: string | null, format?: string) {
  const { getSetting } = useAuth();
  const timezone = getSetting("general", "timezone") ?? "UTC";
  if (!format) {
    format = getSetting("general", "datetime_format") ?? "YYYY-MM-DD HH:mm";
  }

  return moment(datetime).tz(timezone).format(format);
}
