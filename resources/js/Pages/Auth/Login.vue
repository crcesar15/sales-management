<script setup lang="ts">
import { InputText, Password, Button, Checkbox, IconField, InputIcon, useToast, Toast } from "primevue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useAuthClient } from "@/Composables/useAuthClient";

// Set composables
const { t } = useI18n();
const { loginApi } = useAuthClient();
const toast = useToast();

// Form Variables
const username = ref("");
const password = ref("");
const remember = ref(false);
const btnLoading = ref(false);

// Methods
const login = async () => {
  btnLoading.value = true;
  try {
    await loginApi({
      username: username.value,
      password: password.value,
      remember: remember.value,
    });
    router.visit(route("home"));
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error.response?.data?.message ?? "An unexpected error occurred."),
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
          <pattern id="grid-login" width="40" height="40" patternUnits="userSpaceOnUse">
            <circle cx="20" cy="20" r="1.5" fill="white" />
          </pattern>
          <rect width="100%" height="100%" fill="url(#grid-login)" />
        </svg>
      </div>

      <!-- Top: Logo -->
      <div class="relative z-10 invert brightness-0">
        <img src="/images/logo.png" alt="logo" class="h-40" />
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

    <!-- Right Panel: Login Form -->
    <div class="w-full lg:w-7/12 flex items-center justify-center bg-surface-0 px-6 py-12">
      <div class="w-full max-w-md">
        <!-- Mobile logo (visible only on small screens) -->
        <div class="flex justify-center mb-8 lg:hidden">
          <img src="/images/logo.png" alt="logo" class="h-60" />
        </div>

        <!-- Heading -->
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-surface-900">
            {{ t("Welcome Back") }}
          </h2>
          <p class="mt-2 text-surface-500">
            {{ t("Sign in to your account") }}
          </p>
        </div>

        <form @submit.prevent="login">
          <!-- Username -->
          <div class="flex flex-col gap-2">
            <label for="username" class="text-surface-700 font-medium text-sm">{{ t("Username") }}</label>
            <IconField>
              <InputIcon class="fa fa-user" />
              <InputText
                id="username"
                v-model="username"
                class="w-full"
                autocomplete="username"
                :required="true"
                :placeholder="t('Enter your username')"
              />
            </IconField>
          </div>

          <!-- Password -->
          <div class="flex flex-col gap-2 mt-5">
            <label for="password" class="text-surface-700 font-medium text-sm">{{ t("Password") }}</label>
            <IconField>
              <InputIcon class="fa fa-lock" />
              <Password
                v-model="password"
                class="w-full"
                input-class="w-full"
                toggle-mask
                input-id="password"
                :required="true"
                :feedback="false"
                :placeholder="t('Enter your password')"
              />
            </IconField>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center mt-5">
            <Checkbox v-model="remember" binary input-id="remember" />
            <label class="ml-2 text-sm text-surface-600 cursor-pointer" for="remember">{{ t("Remember Me") }}</label>
          </div>

          <!-- Login Button (protagonist) -->
          <div class="mt-6">
            <Button type="submit" class="w-full" :label="t('Login')" :loading="btnLoading" raised />
          </div>

          <!-- Forgot Password (always visible) -->
          <div class="flex justify-end mt-4">
            <a class="text-primary text-sm cursor-pointer hover:underline" @click="router.visit(route('password.reset.request'))">
              {{ t("Forgot Your Password?") }}
            </a>
          </div>
        </form>
      </div>
    </div>

    <Toast />
  </div>
</template>
