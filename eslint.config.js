// Use default imports to safely handle both ESM and CommonJS packages
import vuePkg from "eslint-plugin-vue";
import tsPkg from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import jestPkg from "eslint-plugin-jest";
import prettierConfig from "eslint-config-prettier";
import globals from "globals";

// Extract plugins and configs safely
const vuePlugin = vuePkg;
const tsPlugin = tsPkg;
const jestPlugin = jestPkg;

const vueConfigs = vuePkg.configs;
const tsConfigs = tsPkg.configs;
const jestConfigs = jestPkg.configs;

export default [
  // 1. Ignore files
  {
    ignores: ["dist/**", "node_modules/**"]
  },

  // 2. Global Environment Settings
  {
    files: ["**/*.{js,mjs,cjs,ts,tsx,vue}"],
    languageOptions: {
      globals: {
        ...globals.node,
        ...globals.browser,
        ...globals.es2021,
        Vue: "writable",
        _: "writable",
        axios: "writable",
        route: "writable",
        defineOptions: "readonly",
      },
    },
    plugins: {
      vue: vuePlugin,
      "@typescript-eslint": tsPlugin,
    },
  },

  // 3. Base Recommended Rules
  ...tsConfigs["flat/recommended"],
  ...vueConfigs["flat/strongly-recommended"],

  // 4. Configure TypeScript parser specifically inside <script> blocks of Vue files
  {
    files: ["**/*.vue"],
    languageOptions: {
      parserOptions: {
        parser: tsParser,
        sourceType: "module",
        ecmaVersion: 2020,
        extraFileExtensions: [".vue"],
      },
    },
  },

  // 5. Your Custom Rules
  {
    files: ["**/*.{js,mjs,cjs,ts,tsx,vue}"],
    rules: {
      "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
      "no-debugger": process.env.NODE_ENV === "production" ? "warn" : "off",
      "max-len": [
        "error",
        { code: 140, ignoreComments: true, ignoreStrings: true, ignoreTemplateLiterals: true },
      ],
      "quotes": ["error", "double"],
      "semi": ["error", "always"],
      "vue/multi-word-component-names": [
        "error",
        { ignores: ["Index", "Home", "Login", "Error"] },
      ],
      "vue/require-default-prop": "off",
      "vue/attribute-hyphenation": ["error", "always"],
      "vue/html-self-closing": [
        "error",
        {
          html: { void: "always", normal: "never", component: "always" },
          svg: "always",
          math: "always",
        },
      ],
      "@typescript-eslint/no-explicit-any": "off",
      "@typescript-eslint/no-unused-vars": [
        "error",
        { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
      ],
      "@typescript-eslint/consistent-type-imports": [
        "error",
        { prefer: "type-imports", fixStyle: "inline-type-imports" },
      ],

      "vue/no-mutating-props": "error",
      "vue/no-unused-refs": "error",
      "vue/no-ref-as-operand": "error",
      "vue/require-explicit-emits": "error",
      "vue/no-constant-condition": "warn",
      "vue/no-useless-v-bind": "error",
      "vue/no-useless-mustaches": "error",
      "vue/no-useless-template-attributes": "error",
      "vue/padding-line-between-blocks": ["error", "always"],
    },
  },

  // 5. Prettier — MUST be last to override conflicting formatting rules
  prettierConfig,

  // 6. Test Overrides
  {
    files: ["tests/**/*.js", "tests/**/*.ts"],
    plugins: {
      jest: jestPlugin,
    },
    ...jestConfigs["flat/recommended"],
  },
];
