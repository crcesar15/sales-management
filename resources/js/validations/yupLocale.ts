import { setLocale } from "yup";

export function configureYupLocale(t: (key: string, options?: Record<string, unknown>) => string): void {
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
