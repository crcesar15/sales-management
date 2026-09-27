import { useAuth } from "@/Composables/useAuth";
import { computed } from "vue";
import moment from "moment-timezone";

const DATE_PICKER_FORMATS: Record<string, string> = {
  "YYYY-MM-DD": "yy-mm-dd",
  "DD/MM/YYYY": "dd/mm/yy",
  "MM/DD/YYYY": "mm/dd/yy",
  "DD-MM-YYYY": "dd-mm-yy",
};

export function useDatetimeFormatter() {
  const { getSetting } = useAuth();
  const timezone = getSetting("general", "timezone") ?? "UTC";
  const datetimeFormat = getSetting("general", "datetime_format") ?? "YYYY-MM-DD HH:mm";
  const dateFormat = getSetting("general", "date_format") ?? "YYYY-MM-DD";
  const datePickerFormat = computed(() => {
    const configuredFormat = getSetting("general", "date_format", "YYYY-MM-DD") ?? "YYYY-MM-DD";

    return DATE_PICKER_FORMATS[configuredFormat] ?? "yy-mm-dd";
  });

  function formatDatetime(date: string | null): string {
    if (!date) return "---";
    return moment(date).tz(timezone).format(datetimeFormat);
  }

  function formatDate(date: string | null): string {
    if (!date) return "---";
    return moment(date).tz(timezone).format(dateFormat);
  }

  return { formatDatetime, formatDate, datePickerFormat };
}
