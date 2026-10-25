<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Button, Dialog, ProgressSpinner } from "primevue";
import { useBarcodeScanner, type ScannerErrorType } from "@composables/useBarcodeScanner";

const emit = defineEmits<{
  (e: "detected", code: string): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();

const video = ref<HTMLVideoElement>();
const starting = ref(false);
const error = ref<ScannerErrorType | null>(null);

const { start, stop } = useBarcodeScanner((code: string) => {
  emit("detected", code);
});

const showRetry = computed(() => error.value !== null && error.value !== "unsupported" && error.value !== "insecure-context");

const errorMessage = computed<string>(() => {
  switch (error.value) {
    case "unsupported":
      return t("Camera scanning is not supported in this browser. Try Chrome, Edge or Safari.");
    case "insecure-context":
      return t("Camera access requires a secure (HTTPS) connection.");
    case "permission-denied":
      return t("Camera permission denied. Allow camera access in your browser settings and try again.");
    case "no-camera":
      return t("No camera was found on this device.");
    default:
      return t("Unable to start the camera");
  }
});

const errorIcon = computed<string>(() => {
  switch (error.value) {
    case "unsupported":
    case "permission-denied":
    case "no-camera":
      return "fa fa-camera-slash";
    case "insecure-context":
      return "fa fa-lock";
    default:
      return "fa fa-triangle-exclamation";
  }
});

async function startScanner(): Promise<void> {
  if (!visible.value) return;

  starting.value = true;
  error.value = null;
  await nextTick();

  const videoEl = video.value;
  if (videoEl === undefined) {
    starting.value = false;
    error.value = "unknown";
    return;
  }

  const startError = await start(videoEl);
  starting.value = false;

  if (!visible.value) {
    stop();
    return;
  }
  error.value = startError;
}

function stopScanner(): void {
  stop();
  starting.value = false;
  error.value = null;
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    dismissable-mask
    :header="t('Scan product')"
    :draggable="false"
    :style="{ width: 'min(440px, 94vw)' }"
    data-testid="pos-scanner-dialog"
    @show="startScanner"
    @hide="stopScanner"
  >
    <div class="flex flex-col gap-3">
      <div class="relative overflow-hidden rounded-lg border border-surface-200 bg-black dark:border-surface-700">
        <video ref="video" autoplay muted playsinline class="h-64 w-full object-cover sm:h-72" :aria-label="t('Camera preview')" />
        <div v-if="starting" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80">
          <ProgressSpinner style="width: 36px; height: 36px" />
          <span class="text-white">{{ t("Starting camera...") }}</span>
        </div>
        <div v-else-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 px-6 text-center">
          <i :class="errorIcon" class="text-3xl text-white" aria-hidden="true" />
          <p class="m-0 font-medium text-white">{{ errorMessage }}</p>
          <Button v-if="showRetry" :label="t('Retry')" size="small" severity="secondary" :disabled="starting" @click="startScanner" />
        </div>
        <div v-else class="pointer-events-none absolute inset-0 flex items-center justify-center">
          <div class="h-40 w-4/5 rounded-xl border-2 border-white/80" />
        </div>
      </div>
      <p class="m-0 text-center text-sm text-surface-500 dark:text-surface-400">{{ t("Point the camera at a barcode or QR code") }}</p>
    </div>

    <template #footer>
      <Button :label="t('Done')" severity="secondary" @click="visible = false" />
    </template>
  </Dialog>
</template>