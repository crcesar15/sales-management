<template>
  <div
    class="grid grid-cols-12"
    style="height: calc(100vh - 40px)"
  >
    <div class="col-span-12 lg:col-span-4 lg:col-start-5 md:col-span-6 md:col-start-4 mx-4 md:mx-0 flex flex-col justify-center">
      <div class="bg-surface-0 pt-20 pb-8 px-6 shadow-lg rounded-border w-full">
        <form
          method="POST"
          @submit.prevent="resetPassword"
        >
          <div class="logo-container">
            <img
              src="/images/logo.png"
              alt="logo"
            />
          </div>
          <div class="grid">
            <label
              for="email"
              class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end"
            >Email Address</label>
            <div class="md:col-6 col-12">
              <InputText
                v-model="email"
                type="email"
                class="w-full"
                required
                autocomplete="email"
                autofocus
              />
            </div>
          </div>

          <div class="grid mt-4">
            <label
              for="password"
              class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end"
            >Password</label>
            <div class="md:col-6 col-12">
              <Password
                v-model="password"
                class="w-full"
                inputClass="w-full"
                :feedback="false"
                required
                autocomplete="new-password"
                toggleMask
              />
            </div>
          </div>

          <div class="grid mt-4">
            <label
              for="password-confirm"
              class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end"
            >Confirm Password</label>
            <div class="md:col-6 col-12">
              <Password
                v-model="passwordConfirmation"
                class="w-full"
                inputClass="w-full"
                :feedback="false"
                required
                autocomplete="new-password"
                toggleMask
              />
            </div>
          </div>

          <div class="flex flex-wrap justify-center mt-4">
            <Button
              type="submit"
              :loading="btnLoading"
            >
              Reset Password
            </Button>
            <Toast />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import {
  InputText,
  Password,
  Button,
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
const { resetPasswordApi } = useAuthClient();
const toast = useToast();

// Define props
const props = defineProps<{
  token: string;
  email: string;
}>();

// Form variables
const email = ref(props.email);
const password = ref("");
const passwordConfirmation = ref("");
const btnLoading = ref(false);

// Reset Password function
const resetPassword = async () => {
  btnLoading.value = true;
  try {
    await resetPasswordApi({
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      token: props.token,
    });
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Your password has been reset successfully."),
      life: 3000,
    });
    router.visit(route("login"));
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error.response.data.message || t("An error occurred while resetting your password."),
      life: 5000,
    });
  } finally {
    btnLoading.value = false;
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
