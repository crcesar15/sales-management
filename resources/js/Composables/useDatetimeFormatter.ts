import useSetting from "./useSettings";
import moment from "moment-timezone";

export default function useDatetimeFormatter(datetime: string|null, format?: string) {
  const timezone = useSetting("system", "timezone") ?? 'UTC';
  if (!format) {
    format = useSetting("system", "datetime_format") ?? 'YYYY-mm-dd HH:mm';
  }

  return moment(datetime).tz(timezone).format(format);
}
