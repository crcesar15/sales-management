<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

const appName = computed<string>(() => page.props.appConfig?.name || "Sales Management");
</script>

