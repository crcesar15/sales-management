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
        "resources/js/app.ts",
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
      "vue": "vue/dist/vue.esm-bundler.js",
      "ziggy-js": path.resolve("vendor/tightenco/ziggy"),
      "@": path.resolve(__dirname, "resources/js"),
      "@components": path.resolve(__dirname, "resources/js/Components"),
      "@pages": path.resolve(__dirname, "resources/js/Pages"),
      "@composables": path.resolve(__dirname, "resources/js/Composables"),
      "@app-types": path.resolve(__dirname, "resources/js/Types"),
      "@layouts": path.resolve(__dirname, "resources/js/Layouts"),
      "@directives": path.resolve(__dirname, "resources/js/Directives"),
    },
  },
});
