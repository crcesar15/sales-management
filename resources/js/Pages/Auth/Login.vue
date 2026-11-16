<script setup lang="ts">
import { InputText, Password, Button, Checkbox, IconField, InputIcon, useToast, Toast } from "primevue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useAuthClient } from "@/Composables/useAuthClient";

defineProps<{ businessName: string }>();

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

    <!-- Right Panel: Login Form -->
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
                class="min-h-12 w-full text-base lg:min-h-0"
                autocomplete="username"
                autocapitalize="none"
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
                input-class="min-h-12 w-full pr-12 text-base lg:min-h-0"
                toggle-mask
                input-id="password"
                :input-props="{ autocomplete: 'current-password' }"
                :required="true"
                :feedback="false"
                :placeholder="t('Enter your password')"
              >
                <template #maskicon="{ toggleCallback }">
                  <button type="button" class="password-toggle" :aria-label="t('Hide password')" @click="toggleCallback">
                    <i class="fa fa-eye-slash" aria-hidden="true" />
                  </button>
                </template>
                <template #unmaskicon="{ toggleCallback }">
                  <button type="button" class="password-toggle" :aria-label="t('Show password')" @click="toggleCallback">
                    <i class="fa fa-eye" aria-hidden="true" />
                  </button>
                </template>
              </Password>
            </IconField>
          </div>

          <!-- Remember Me -->
          <div class="flex min-h-11 items-center mt-3 lg:mt-5 lg:min-h-0">
            <Checkbox v-model="remember" binary input-id="remember" />
            <label class="ml-2 flex min-h-11 items-center text-sm text-surface-700 cursor-pointer lg:min-h-0" for="remember">{{ t("Remember Me") }}</label>
          </div>

          <!-- Login Button (protagonist) -->
          <div class="mt-6">
            <Button type="submit" class="min-h-12 w-full lg:min-h-0" :label="t('Login')" :loading="btnLoading" raised />
          </div>

          <!-- Forgot Password (always visible) -->
          <div class="flex justify-end mt-2 lg:mt-4">
            <Link :href="route('password.reset.request')" class="inline-flex min-h-11 items-center text-primary text-sm hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:min-h-0">
              {{ t("Forgot Your Password?") }}
            </Link>
          </div>
        </form>
      </div>
    </div>

    <Toast />
  </div>
</template>

<style scoped>
.password-toggle {
  position: absolute;
  top: 50%;
  right: 0;
  display: flex;
  width: 3rem;
  height: 100%;
  align-items: center;
  justify-content: center;
  transform: translateY(-50%);
  color: var(--p-surface-600);
}

.password-toggle:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: -4px;
}
</style>
