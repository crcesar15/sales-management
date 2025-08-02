import useSetting from "./useSettings";

export default function useDatetimeFormatter(datetime) {
  const timezone = useSetting("system", "timezone");
  const datetimeFormat = useSetting("system", "datetime_format");

  return window.moment(datetime).tz(timezone).format(datetimeFormat);
}
