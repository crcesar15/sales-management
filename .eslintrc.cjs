// .eslint.cjs
module.exports = {
  root: true,
  env: {
    node: true,
    browser: true,
    es2021: true,
  },
  globals: {
    Vue: true,
    _: true,
    axios: true,
    route: true,
    defineOptions: "readonly",
  },
  extends: [
    "eslint:recommended",
    "plugin:vue/vue3-recommended",
    "airbnb-base",
    "plugin:@typescript-eslint/recommended",
    "prettier",
  ],
  parser: "vue-eslint-parser", // ✅ Handles <template>
  parserOptions: {
    parser: "@typescript-eslint/parser", // ✅ Handles <script lang="ts">
    sourceType: "module",
    ecmaVersion: 2020,
    extraFileExtensions: [".vue"],
  },
  plugins: ["vue", "@typescript-eslint"],
  rules: {
    quotes: ["error", "double"],
    semi: ["error", "always"],
    "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
    "no-debugger": process.env.NODE_ENV === "production" ? "warn" : "off",
    "vue/multi-word-component-names": [
      "error",
      { ignores: ["Index", "Home", "Login", "Error"] },
    ],
    "vue/require-default-prop": "off", // Optional props without default
    "vue/attribute-hyphenation": ["error", "never"], // Allow camelCase props in templates
    "vue/html-self-closing": [
      "error",
      {
        html: { void: "always", normal: "never", component: "always" },
        svg: "always",
        math: "always",
      },
    ],
    "@typescript-eslint/no-explicit-any": "off", // Allow `any` when needed
    "@typescript-eslint/explicit-module-boundary-types": "off",
    "@typescript-eslint/no-unused-vars": [
      "error",
      { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
    ],
    "max-len": [
      "error",
      { code: 140, ignoreComments: true, ignoreStrings: true, ignoreTemplateLiterals: true },
    ],
  },
  overrides: [
    {
      files: ["tests/**/*.js", "tests/**/*.ts"],
      plugins: ["jest"],
      extends: ["plugin:jest/recommended"],
    },
  ],
};