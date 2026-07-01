<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import PosLayout from "@layouts/pos.vue";
import RegisterSelectDialog from "./Components/RegisterSelectDialog.vue";
import { usePosStore } from "@/Composables/usePosStore";
import { usePosClient } from "@/Composables/usePosClient";
import { usePage } from "@inertiajs/vue3";

defineOptions({ layout: PosLayout });

const { t } = useI18n();
const posStore = usePosStore();
const posClient = usePosClient();
const page = usePage();

const showRegisterDialog = ref(false);

onMounted(async () => {
  // Set user info from Inertia page props
  const authUser = page.props.auth?.user as unknown as { id: number; name: string; email: string } | undefined;
  if (authUser) {
    posStore.setUserId(authUser.id);
  }

  // Check if user has an active POS session via the API
  try {
    const session = await posClient.getSession();

    if (session.store) {
      posStore.setStore(session.store);
    }

    if (session.register) {
      posStore.setRegister(session.register);
    }

    if (session.shift) {
      posStore.setShift(session.shift);
    }

    // No open shift — show register selection dialog
    if (!session.shift || session.shift.status !== "open") {
      showRegisterDialog.value = true;
    }
  } catch {
    // API not available yet (01b not implemented) — show register dialog
    // Once 01b is implemented, this will properly fetch the session
    if (!posStore.hasShift) {
      showRegisterDialog.value = true;
    }
  }
});
</script>

<template>
  <div class="p-6">
    <h2 class="text-2xl font-semibold mb-4">{{ t("Point of Sale") }}</h2>
    <p class="text-surface-500 dark:text-surface-400">
      {{ t("POS Interface") }}
    </p>
    <!-- POS Interface content will be added in Task 02 -->

    <RegisterSelectDialog v-model:visible="showRegisterDialog" />
  </div>
</template>
