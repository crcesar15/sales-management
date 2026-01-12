<template>
  <div class="grid">
    <div class="col-12 md:col-8 md:col-offset-2">
      <div class="surface-card p-4 shadow-2 border-round w-full">
        <div class="logo-container">
          <img
            src="images/logo.png"
            alt="logo"
          />
        </div>

        <form
          method="POST"
          @submit.prevent="resetPassword"
        >
          <input
            id="token"
            type="text"
            value=""
            style="display: none;"
          />
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

          <div class="grid">
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

          <div class="grid">
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

          <div class="flex flex-wrap justify-center">
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

// Form variables
const email = ref("");
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
      token: (new URLSearchParams(window.location.search)).get("token") || "",
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
