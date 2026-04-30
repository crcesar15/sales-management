<script setup lang="ts">
import { Card, Button, InputText } from "primevue";
import { useI18n } from "vue-i18n";
import type { AdditionalContact } from "@/Types/vendor-types";

const { t } = useI18n();

const contacts = defineModel<AdditionalContact[]>({ required: true });

const addContact = () => {
  contacts.value = [...contacts.value, { name: "", phone: "", email: "", role: "" }];
};

const removeContact = (index: number) => {
  contacts.value = contacts.value.filter((_, i) => i !== index);
};
</script>

<template>
  <div>
    <div v-if="contacts.length === 0" class="text-surface-400 text-center py-4">
      {{ t("No additional contacts added") }}
    </div>
    <div v-for="(contact, index) in contacts" :key="index" class="border-2 rounded-border border-surface p-3 mb-3">
      <div class="flex justify-end mb-2">
        <Button
          v-tooltip.top="t('Remove Contact')"
          icon="fa fa-trash"
          size="small"
          outlined
          raised
          class="!p-3 !m-0 !min-w-[2rem] !h-[2rem]"
          @click="removeContact(index)"
        />
      </div>
      <div class="grid grid-cols-12 gap-3">
        <div class="lg:col-span-3 md:col-span-6 col-span-12">
          <div class="flex flex-col gap-1">
            <label :for="`contact-name-${index}`">
              {{ t("Contact Name") }}
              <span class="text-red-500">*</span>
            </label>
            <InputText :id="`contact-name-${index}`" v-model="contact.name" autocomplete="off" />
          </div>
        </div>
        <div class="lg:col-span-3 md:col-span-6 col-span-12">
          <div class="flex flex-col gap-1">
            <label :for="`contact-phone-${index}`">{{ t("Phone") }}</label>
            <InputText :id="`contact-phone-${index}`" v-model="contact.phone" autocomplete="off" />
          </div>
        </div>
        <div class="lg:col-span-3 md:col-span-6 col-span-12">
          <div class="flex flex-col gap-1">
            <label :for="`contact-email-${index}`">{{ t("Email") }}</label>
            <InputText :id="`contact-email-${index}`" v-model="contact.email" autocomplete="off" />
          </div>
        </div>
        <div class="lg:col-span-3 md:col-span-6 col-span-12">
          <div class="flex flex-col gap-1">
            <label :for="`contact-role-${index}`">{{ t("Role") }}</label>
            <InputText :id="`contact-role-${index}`" v-model="contact.role" autocomplete="off" />
          </div>
        </div>
      </div>
    </div>
    <div class="flex justify-end">
      <Button icon="fa fa-plus" text :label="t('Add Contact')" @click="addContact" />
    </div>
  </div>
</template>
