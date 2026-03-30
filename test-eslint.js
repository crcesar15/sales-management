import vuePkg from "eslint-plugin-vue";
import tsPkg from "@typescript-eslint/eslint-plugin";
console.log(vuePkg.configs["flat/strongly-recommended"].map(c => Object.keys(c)));
console.log(tsPkg.configs["flat/recommended"].map(c => Object.keys(c)));
