<script setup lang="ts">
import { InputText, Button, IconField, InputIcon, Toast, useToast } from "primevue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useAuthClient } from "@/Composables/useAuthClient";

defineProps<{ businessName: string }>();

// Set composables
const { t } = useI18n();
const { requestResetPasswordApi } = useAuthClient();
const toast = useToast();

// Form Variables
const email = ref("");
const btnLoading = ref(false);

// Methods
const sentResetLink = async () => {
  btnLoading.value = true;
  try {
    await requestResetPasswordApi({
      email: email.value,
    });
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("A password reset link has been sent to your email address."),
      life: 5000,
    });
    email.value = "";
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error.response?.data?.message || t("An error occurred while sending the password reset link."),
      life: 5000,
    });
  } finally {
    btnLoading.value = false;
  }
};
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Left Panel: Branding (hidden on mobile) -->
    <div
      class="hidden lg:flex lg:w-5/12 flex-col justify-between p-12 relative overflow-hidden"
      style="background: linear-gradient(135deg, #2563eb 0%, #1e3a8a 100%)"
    >
      <!-- Grid dot pattern overlay -->
      <div class="absolute inset-0 opacity-20">
        <svg width="100%" height="100%">
          <pattern id="grid-email" width="40" height="40" patternUnits="userSpaceOnUse">
            <circle cx="20" cy="20" r="1.5" fill="white" />
          </pattern>
          <rect width="100%" height="100%" fill="url(#grid-email)" />
        </svg>
      </div>

      <!-- Top: Business name -->
      <div class="relative z-10">
        <p class="text-3xl font-bold text-white break-words uppercase">{{ businessName }}</p>
      </div>

      <!-- Center: Value proposition -->
      <div class="relative z-10">
        <h1 class="text-4xl font-bold leading-tight mb-4 text-white w-2/3">
          {{ t("Sales & Inventory Management") }}
        </h1>
        <p class="text-lg text-white/80 leading-relaxed w-2/3">
          {{ t("Streamline your operations, track inventory in real time, and make data-driven decisions.") }}
        </p>
      </div>

      <!-- Bottom: Subtle footer -->
      <div class="relative z-10 flex justify-end">
        <p class="text-sm text-white/60">&copy; {{ new Date().getFullYear() }}</p>
      </div>
    </div>

    <!-- Right Panel: Reset Password Form -->
    <div class="w-full lg:w-7/12 flex justify-center bg-gradient-to-b from-surface-100 to-surface-0 px-5 py-8 sm:px-8 lg:items-center lg:px-6 lg:py-12">
      <div class="my-auto w-full max-w-md">
        <!-- Mobile business name (visible only on small screens) -->
        <div class="mb-10 flex items-center gap-3 border-b border-surface-200 pb-6 lg:hidden">
          <span aria-hidden="true" class="h-9 w-1 shrink-0 rounded-full bg-primary" />
          <p class="min-w-0 break-words text-xl font-bold text-surface-900 uppercase">{{ businessName }}</p>
        </div>

        <!-- Heading -->
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-surface-900">
            {{ t("Forgot Password") }}
          </h2>
          <p class="mt-2 text-surface-500">
            {{ t("Enter your email to receive a reset link") }}
          </p>
        </div>

        <form @submit.prevent="sentResetLink">
          <!-- Email -->
          <div class="flex flex-col gap-2">
            <label for="email" class="text-surface-700 font-medium text-sm">{{ t("Email Address") }}</label>
            <IconField>
              <InputIcon class="fa fa-envelope" />
              <InputText
                id="email"
                v-model="email"
                type="email"
                class="min-h-12 w-full text-base lg:min-h-0"
                autocomplete="email"
                :required="true"
                :placeholder="t('Enter your email address')"
              />
            </IconField>
          </div>

          <!-- Submit Button (protagonist) -->
          <div class="mt-6">
            <Button type="submit" class="min-h-12 w-full lg:min-h-0" :label="t('Send Password Reset Link')" :loading="btnLoading" raised />
          </div>

          <!-- Back to Login -->
          <div class="flex justify-center mt-2 lg:mt-4">
            <Link :href="route('login')" class="inline-flex min-h-11 items-center text-primary text-sm hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:min-h-0">
              {{ t("Back to Login") }}
            </Link>
          </div>
        </form>
      </div>
    </div>

    <Toast />
  </div>
</template>
