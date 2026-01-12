<template>
  <div
    class="grid grid-cols-12"
    style="height: calc(100vh - 40px)"
  >
    <div class="col-span-12 lg:col-span-4 lg:col-start-5 md:col-span-6 md:col-start-4 mx-4 md:mx-0 flex flex-col justify-center">
      <div class="bg-surface-0 pt-20 pb-8 px-6 shadow-lg rounded-border w-full">
        <form
          method="POST"
          @submit.prevent="sentResetLink"
        >
          <div class="grid">
            <div class="logo-container">
              <img
                src="/images/logo.png"
                alt="logo"
              />
            </div>
            <div class="mt-4 md:col-4 flex flex-wrap align-content-center justify-content-end">
              <label for="email">{{ t("Email Address") }}</label>
            </div>
            <div class="mt-4 md:col-6">
              <InputText
                id="email"
                v-model="email"
                class="w-full"
                required
                autocomplete="email"
              />
            </div>
          </div>
          <div class="grid mt-4">
            <div class="md:col-8 md:col-offset-2 flex flex-wrap justify-center">
              <Button
                type="submit"
                :loading="btnLoading"
              >
                {{ t("Send Password Reset Link") }}
              </Button>
              <Toast />
            </div>
          </div>
        </form>
        <div
          class="mt-4"
          style="text-align: right"
        >
          <a
            class="text-primary cursor-pointer"
            @click="router.visit(route('login'))"
          >
            {{ t("Go Back") }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import {
  InputText,
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
    console.log(error);
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
