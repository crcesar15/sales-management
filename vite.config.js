import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";
import tailwindcss from "tailwindcss";

export default defineConfig({
  css: {
    preprocessorOptions: {
      scss: {
        api: "modern", // or "modern"
      },
    },
    postcss: {
      plugins: [
        tailwindcss,
      ],
    },
  },
  plugins: [
    laravel({
      input: [
        "resources/js/app.js",
        "resources/js/login/index.js",
        "resources/sass/app.scss",
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      vue: "vue/dist/vue.esm-bundler.js",
      "ziggy-js": path.resolve("vendor/tightenco/ziggy"),
    },
  },
});
