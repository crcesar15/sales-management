import { useAuth } from "@/Composables/useAuth";
import { computed } from "vue";
import moment from "moment-timezone";

const DATE_PICKER_FORMATS: Record<string, string> = {
  "YYYY-MM-DD": "yy-mm-dd",
  "DD/MM/YYYY": "dd/mm/yy",
  "MM/DD/YYYY": "mm/dd/yy",
  "DD-MM-YYYY": "dd-mm-yy",
};

function extractDateOnly(date: string): string | null {
  return date.match(/^\d{4}-\d{2}-\d{2}/)?.[0] ?? null;
}

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

  function formatDateOnly(date: string | null): string {
    if (!date) return "---";

    const dateOnly = extractDateOnly(date);
    if (!dateOnly) return "---";

    return moment(dateOnly, "YYYY-MM-DD", true).format(dateFormat);
  }

  function parseDateOnly(date: string | null): Date | null {
    if (!date) return null;

    const dateOnly = extractDateOnly(date);
    if (!dateOnly || !moment(dateOnly, "YYYY-MM-DD", true).isValid()) return null;

    const [year, month, day] = dateOnly.split("-").map(Number);

    return new Date(year, month - 1, day);
  }

  function toDateOnly(date: Date | null | undefined): string | null {
    if (!date || Number.isNaN(date.getTime())) return null;

    return `${String(date.getFullYear())}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
  }

  return { formatDatetime, formatDate, formatDateOnly, parseDateOnly, toDateOnly, datePickerFormat };
}
