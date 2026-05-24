import { useAuth } from "@/Composables/useAuth";
import moment from "moment-timezone";

export function useDatetimeFormatter() {
  const { getSetting } = useAuth();
  const timezone = getSetting("general", "timezone") ?? "UTC";
  const datetimeFormat = getSetting("general", "datetime_format") ?? "YYYY-MM-DD HH:mm";
  const dateFormat = getSetting("general", "date_format") ?? "YYYY-MM-DD";

  function formatDatetime(date: string | null): string {
    if (!date) return "---";
    return moment(date).tz(timezone).format(datetimeFormat);
  }

  function formatDate(date: string | null): string {
    if (!date) return "---";
    return moment(date).tz(timezone).format(dateFormat);
  }

  return { formatDatetime, formatDate };
}