import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";
import tailwindcss from "tailwindcss";

export default defineConfig({
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
  css: {
    postcss: {
      plugins: [
        tailwindcss,
      ],
    },
  },
  resolve: {
    alias: {
      vue: "vue/dist/vue.esm-bundler.js",
      "ziggy-js": path.resolve("vendor/tightenco/ziggy"),
    },
  },
});
