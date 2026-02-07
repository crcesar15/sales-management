<script setup lang="ts">
import SelectButton from "primevue/selectbutton";
import { useTheme } from "./Composables/useTheme";

const {
  preset,
  presetOptions,
  menuMode,
  menuModeOptions,
  primaryColors,
  surfaces,
  layoutConfig,
  isDarkTheme,
  updateColors,
  onPresetChange,
  onMenuModeChange,
} = useTheme();
</script>

<template>
  <div class="config-panel">
    <div class="flex flex-col gap-4">
      <div>
        <span class="text-sm text-muted-color font-semibold">{{ $t("Primary") }}</span>
        <div class="pt-2 flex gap-2 flex-wrap">
          <button
            v-for="primaryColor of primaryColors"
            :key="primaryColor.name"
            type="button"
            :title="primaryColor.name"
            :class="[
              'border-none w-5 h-5 rounded-full p-0 cursor-pointer outline-none outline-offset-1',
              { 'outline-primary': layoutConfig.primary === primaryColor.name }
            ]"
            :style="{ backgroundColor: `${primaryColor.name === 'noir' ? 'var(--text-color)' : primaryColor.palette['500']}` }"
            @click="updateColors('primary', primaryColor)"
          />
        </div>
      </div>
      <div>
        <span class="text-sm text-muted-color font-semibold">{{ $t("Surface") }}</span>
        <div class="pt-2 flex gap-2 flex-wrap">
          <button
            v-for="surface of surfaces"
            :key="surface.name"
            type="button"
            :title="surface.name"
            :class="[
              'border-none w-5 h-5 rounded-full p-0 cursor-pointer outline-none outline-offset-1',
              { 'outline-primary': layoutConfig.surface ? layoutConfig.surface === surface.name : isDarkTheme ? surface.name === 'zinc' : surface.name === 'slate' }
            ]"
            :style="{ backgroundColor: `${surface.palette['500']}` }"
            @click="updateColors('surface', surface)"
          />
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <span class="text-sm text-muted-color font-semibold">{{ $t("Presets") }}</span>
        <SelectButton
          v-model="preset"
          :options="presetOptions"
          :allow-empty="false"
          @change="onPresetChange"
        />
      </div>
      <div class="flex flex-col gap-2">
        <span class="text-sm text-muted-color font-semibold">{{ $t("Menu Mode") }}</span>
        <SelectButton
          v-model="menuMode"
          :options="menuModeOptions"
          :allow-empty="false"
          option-label="label"
          option-value="value"
          @change="onMenuModeChange"
        />
      </div>
    </div>
  </div>
</template>
