import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { quasar, transformAssetUrls } from "@quasar/vite-plugin";

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/js/app.js",
        "resources/css/app.css",
        "resources/js/products/index.js",
        "resources/css/bootstrap.min.css",
      ],
      refresh: true,
    }),
    vue({
      template: { transformAssetUrls },
    }),
    quasar({
      sassVariables: "/resources/sass/quasar.variables.scss",
    }),
  ],
  resolve: {
    alias: {
      vue: "vue/dist/vue.esm-bundler.js",
    },
  },
});
