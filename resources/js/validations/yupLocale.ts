import { setLocale } from "yup";
import type { Composer } from "vue-i18n";

export function configureYupLocale(t: Composer["t"]): void {
  setLocale({
    mixed: {
      required: () => t("validations.required"),
    },
    string: {
      min: ({ min }) => t("validations.minLength", { min }),
      max: ({ max }) => t("validations.maxLength", { max }),
      email: () => t("validations.email"),
    },
    number: {
      min: ({ min }) => t("validations.minValue", { min }),
    },
  });
}
