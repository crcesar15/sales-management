import useSetting from "./useSettings";
import moment from "moment-timezone";

export default function useDatetimeFormatter(datetime: string|null) {
  const timezone = useSetting("system", "timezone") ?? 'UTC';
  const datetimeFormat = useSetting("system", "datetime_format") ?? 'YYYY-mm-dd HH:mm';

  return moment(datetime).tz(timezone).format(datetimeFormat);
}
