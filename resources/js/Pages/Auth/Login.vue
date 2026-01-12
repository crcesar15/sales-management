<template>
  <div
    class="grid grid-cols-12"
    style="height: calc(100vh - 40px)"
  >
    <div
      class="col-span-12 lg:col-span-4 lg:col-start-5 md:col-span-6 md:col-start-4 mx-4 md:mx-0 flex flex-col justify-center"
    >
      <div class="bg-surface-0 pt-20 pb-8 px-6 shadow-lg rounded-border w-full">
        <form
          method="POST"
          @submit.prevent="login"
        >
          <!--Add logo from public storage-->
          <div class="logo-container">
            <img
              src="/images/logo.png"
              alt="logo"
            />
          </div>
          <div class="flex flex-col gap-2 mt-4">
            <label for="username">{{ t("Username") }}</label>
            <InputText
              v-model="username"
              class="w-full"
              autocomplete="username"
              inputId="username"
              :required="true"
            />
          </div>
          <div class="flex flex-col gap-2 mt-4">
            <label for="password">{{ t("Password") }}</label>
            <Password
              v-model="password"
              class="w-full"
              inputClass="w-full"
              toggleMask
              inputId="password"
              :required="true"
              :feedback="false"
            />
          </div>
          <div class="flex mt-4">
            <Checkbox
              v-model="remember"
              binary
              inputId="remember"
              class="pt-1"
            />
            <label
              class="text-danger ml-2"
              for="remember"
            >{{ t("Remember Me") }}</label>
          </div>
          <div class="flex w-full mt-3 gap-2">
            <div class="w-full">
              <Button
                type="submit"
                class="w-full"
                :label="t('Login')"
              />
            </div>
          </div>
          <div class="flex justify-end w-full mt-4">
            <a
              v-if="showResetPassword"
              class="text-primary cursor-pointer"
              @click="router.visit(route('auth.password.reset.request'))"
            >
              {{ t("Forgot Your Password?") }}
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
  Password,
  Button,
  Checkbox,
  useToast,
  Toast,
} from "primevue";
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
const showResetPassword = ref(false);

// Methods
const login = async () => {
  try {
    await loginApi({
      username: username.value,
      password: password.value,
      remember: remember.value,
    });
    router.visit(route("home"));
  } catch (error: any) {
    showResetPassword.value = true;
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error.response?.data?.message ?? "An unexpected error occurred."),
    });
  }
};

</script>

<style scoped>
    .register-container {
        margin-top: 20%;
        border-radius: 15px !important;
    }

    .logo-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .logo-container img {
        height: 100px;
    }
</style>
