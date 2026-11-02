<template>
  <details class="mt-4 text-sm">
    <summary class="cursor-pointer font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
      {{ t('demo.curl') }}
    </summary>
    <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100"><code>{{ curl }}</code></pre>
  </details>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

defineProps<{ curl: string }>();
const { t } = useI18n();
</script>
