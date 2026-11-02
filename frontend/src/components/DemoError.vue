<template>
  <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30">
    <p class="font-medium text-red-800 dark:text-red-200">{{ headline }}</p>
    <ul v-if="showViolations && violations.length" class="mt-2 list-disc pl-5 text-sm text-red-700 dark:text-red-300">
      <li v-for="(v, i) in violations" :key="i">
        <span class="font-medium">{{ v.field }}:</span> {{ v.message }}
      </li>
    </ul>
    <p v-if="retryAfter != null" class="mt-2 text-sm text-red-700 dark:text-red-300">
      {{ t('demo.rateLimited', { s: retryAfter }) }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DemoErrorCode, Violation } from '../composables/useDemo';

const props = defineProps<{
  errorCode: DemoErrorCode | null;
  violations: Violation[];
  retryAfter: number | null;
}>();

const { t } = useI18n();

const showViolations = computed(() => props.errorCode === 'validation_failed');
const headline = computed(() => {
  switch (props.errorCode) {
    case 'validation_failed':
      return t('demo.validationError');
    case 'rate_limited':
      return t('demo.rateLimited', { s: props.retryAfter ?? 60 });
    case 'timeout':
      return t('demo.timeout');
    case 'server_error':
      return t('demo.serverError');
    default:
      return t('demo.serverError');
  }
});
</script>
