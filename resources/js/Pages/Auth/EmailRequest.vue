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

      <!-- Top: Logo -->
      <div class="relative z-10 invert brightness-0">
        <img
          src="/images/logo.png"
          alt="logo"
          class="h-40"
        />
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
        <p class="text-sm text-white/60">
          &copy; {{ new Date().getFullYear() }}
        </p>
      </div>
    </div>

    <!-- Right Panel: Reset Password Form -->
    <div class="w-full lg:w-7/12 flex items-center justify-center bg-surface-0 px-6 py-12">
      <div class="w-full max-w-md">
        <!-- Mobile logo (visible only on small screens) -->
        <div class="flex justify-center mb-8 lg:hidden">
          <img
            src="/images/logo.png"
            alt="logo"
            class="h-60"
          />
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
            <label
              for="email"
              class="text-surface-700 font-medium text-sm"
            >{{ t("Email Address") }}</label>
            <IconField>
              <InputIcon class="fa fa-envelope" />
              <InputText
                id="email"
                v-model="email"
                type="email"
                class="w-full"
                autocomplete="email"
                :required="true"
                :placeholder="t('Enter your email address')"
              />
            </IconField>
          </div>

          <!-- Submit Button (protagonist) -->
          <div class="mt-6">
            <Button
              type="submit"
              class="w-full"
              :label="t('Send Password Reset Link')"
              :loading="btnLoading"
              raised
            />
          </div>

          <!-- Back to Login -->
          <div class="flex justify-center mt-4">
            <a
              class="text-primary text-sm cursor-pointer hover:underline"
              @click="router.visit(route('login'))"
            >
              {{ t("Back to Login") }}
            </a>
          </div>
        </form>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import {
  InputText,
  Button,
  IconField,
  InputIcon,
  Toast,
  useToast,
} from "primevue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useAuthClient } from "@/Composables/useAuthClient";

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
