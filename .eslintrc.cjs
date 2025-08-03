module.exports = {
  root: true,
  env: {
    node: true,
    browser: true,
  },
  globals: {
    Vue: true,
    _: true,
    axios: true,
    route: true,
    defineOptions: "readonly",
  },
  extends: ["eslint:recommended", "plugin:vue/recommended", "airbnb-base"],
  parserOptions: {
    parser: "@babel/eslint-parser",
    sourceType: "module",
    ecmaVersion: 2020,
    babelOptions: {
      configFile: "./babel.config.json",
    },
  },
  plugins: [
    "vue",
  ],
  rules: {
    quotes: ["error", "double"],
    "max-len": ["error", {
      code: 140,
      ignoreComments: true,
    }],
    "vue/multi-word-component-names": ["error", {
      ignores: ["Index"],
    }],
  },
  overrides: [
    {
      files: [
        "tests/**/*.js",
      ],
      plugins: ["jest"],
      extends: ["plugin:jest/recommended"],
    },
  ],
};
