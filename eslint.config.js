import vuePkg from "eslint-plugin-vue";
import tsPkg from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import jestPkg from "eslint-plugin-jest";
import prettierConfig from "eslint-config-prettier";
import globals from "globals";

const vuePlugin = vuePkg;
const tsPlugin = tsPkg;
const jestPlugin = jestPkg;

const vueConfigs = vuePkg.configs;
const tsConfigs = tsPkg.configs;

export default [
  // 1. Ignore files
  {
    ignores: ["dist/**", "node_modules/**", "*.d.ts"],
  },

  // 2. Global Environment Settings
  {
    files: ["**/*.{js,mjs,cjs,ts,tsx,vue}"],
    languageOptions: {
      globals: {
        ...globals.node,
        ...globals.browser,
        // globals.es2021 is not a valid key — browser/node cover what's needed.
        // Removed: Vue (Vue 2 pattern), defineOptions (auto-handled by vue plugin),
        // axios and _ should be imported per file, not declared as globals.
        route: "readonly",
      },
    },
    plugins: {
      vue: vuePlugin,
      "@typescript-eslint": tsPlugin,
    },
  },

  // 3. Base Recommended Rules
  // flat/recommended and flat/strongly-recommended are Vue 3 configs.
  // flat/vue2-* variants are for Vue 2.
  // flat/recommended adds vue/attributes-order and vue/order-of-components on top
  // of flat/strongly-recommended, which is useful with PrimeVue's attribute-heavy components.
  ...tsConfigs["flat/recommended"],
  ...vueConfigs["flat/strongly-recommended"],

  // 4. TypeScript parser for Vue <script> blocks
  {
    files: ["**/*.vue"],
    languageOptions: {
      parserOptions: {
        parser: tsParser,
        sourceType: "module",
        ecmaVersion: "latest",
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
        extraFileExtensions: [".vue"], // ← correct placement
      },
    },
  },

  // TypeScript files
  {
    files: ["**/*.{ts,tsx}"],
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
      },
    },
  },

  // 5. Custom Rules — all file types
  {
    files: ["**/*.{js,mjs,cjs,ts,tsx,vue}"],
    rules: {
      "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
      "no-debugger": process.env.NODE_ENV === "production" ? "warn" : "off",
      "no-unused-vars": "off",

      "@typescript-eslint/no-explicit-any": ["warn", { ignoreRestArgs: true }],

      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
        },
      ],

      "@typescript-eslint/consistent-type-definitions": ["error", "interface"],
      "@typescript-eslint/no-non-null-assertion": "warn",

      // --- Vue: Logic / Correctness ---
      "vue/no-mutating-props": "error",
      "vue/no-unused-refs": "error",
      "vue/no-ref-as-operand": "error",
      "vue/require-explicit-emits": "error",
      "vue/no-constant-condition": "warn",
      "vue/no-useless-v-bind": "error",
      "vue/no-useless-mustaches": "error",
      "vue/no-useless-template-attributes": "error",
      "vue/valid-define-props": "error",
      "vue/valid-define-emits": "error",
      "vue/no-v-html": "warn",

      // --- Vue: Style / Consistency ---
      "vue/multi-word-component-names": [
        "error",
        { ignores: ["Index", "Home", "Login", "Error"] },
      ],
      "vue/require-default-prop": "off",
      "vue/attribute-hyphenation": ["error", "always"],
      "vue/component-api-style": ["error", ["script-setup", "composition"]],
      "vue/block-order": ["error", { order: ["script", "template", "style"] }],
      "vue/define-macros-order": [
        "error",
        { order: ["defineOptions", "defineProps", "defineEmits", "defineSlots"] },
      ],
    },
  },

  // 5b. Type-aware rules — TS and Vue files only (requires parserOptions.project)
  {
    files: ["**/*.{ts,tsx,vue}"],
    rules: {
      "@typescript-eslint/consistent-type-imports": [
        "error",
        { prefer: "type-imports", fixStyle: "inline-type-imports" },
      ],
      "@typescript-eslint/consistent-type-exports": [
        "error",
        { fixMixedExportsWithInlineTypeSpecifier: true },
      ],
      "@typescript-eslint/no-import-type-side-effects": "error",
      "@typescript-eslint/no-useless-constructor": "error",
    },
  },

  // 6. Prettier — MUST be last to disable all formatting-conflicting rules
  prettierConfig,

  // 7. Test file overrides
  {
    files: ["tests/**/*.{js,ts}", "**/*.spec.{js,ts}", "**/*.test.{js,ts}"],
    ...jestPlugin.configs["flat/recommended"],
    rules: {
      ...jestPlugin.configs["flat/recommended"].rules,
    },
  },
];