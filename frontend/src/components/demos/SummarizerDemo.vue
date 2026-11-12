<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Form -->
    <div class="flex flex-col gap-4">
      <h3 class="text-center text-lg font-semibold">{{ t('demoSummarize.title') }}</h3>
      <p class="text-center text-sm text-gray-600 dark:text-gray-400">{{ t('demoSummarize.desc') }}</p>

      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <label for="summarize-url" class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ t('demoSummarize.url') }}
          </label>
          <input
            id="summarize-url"
            v-model.trim="form.url"
            type="url"
            inputmode="url"
            :placeholder="t('demoSummarize.urlPh')"
            :class="inputClass"
          />
          <p v-if="violation('url')" role="alert" class="text-sm text-red-600 dark:text-red-400">
            {{ violation('url') }}
          </p>
        </div>

        <Button :loading="isRunning" :block="true" @click="onSubmit">{{ t('demoSummarize.summarize') }}</Button>
      </div>

      <CurlExample :curl="curlExample" />
    </div>

    <!-- Result -->
    <div aria-live="polite" :aria-busy="isRunning" class="flex flex-col gap-4">
      <h3 class="text-center text-lg font-semibold">{{ t('demoSummarize.tldr') }}</h3>

      <div v-if="isRunning" class="flex items-center gap-2 text-gray-500">
        <Spinner size="sm" /> {{ t('common.loading') }}
      </div>

      <DemoError v-else-if="isError" :error-code="errorCode" :violations="violations" :retry-after="retryAfter" />

      <div v-else-if="isSuccess && result" class="flex flex-col gap-4">
        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
          <p class="text-gray-900 dark:text-gray-100">{{ result.tldr }}</p>
        </div>

        <div class="flex flex-col gap-2">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('demoSummarize.bullets') }}</p>
          <ol class="list-decimal space-y-1 pl-5 text-gray-700 dark:text-gray-300">
            <li v-for="(bullet, i) in result.bullets" :key="i">{{ bullet }}</li>
          </ol>
        </div>
      </div>

      <div v-else class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500 dark:border-gray-700">
        {{ t('demos.stats.empty') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useDemo, type Violation } from '../../composables/useDemo';
import Button from '../Button.vue';
import Spinner from '../Spinner.vue';
import DemoError from '../DemoError.vue';
import CurlExample from '../CurlExample.vue';

interface SummarizeResult {
  tldr: string;
  bullets: string[];
}

const { t } = useI18n();
const curlExample = `
curl -X POST http://localhost:8080/api/demo/url-summarizer
  -H 'Content-Type: application/json'
  -d '{"url":"https://example.com/article"}'
`

const form = reactive({ url: '' });
const demo = useDemo<SummarizeResult>('url-summarizer');

const isRunning = computed(() => demo.status.value === 'loading');
const isSuccess = computed(() => demo.status.value === 'success');
const isError = computed(() => demo.status.value === 'error');
const result = computed(() => demo.data.value);
const violations = computed<Violation[]>(() => demo.violations.value);
const errorCode = computed(() => demo.errorCode.value);
const retryAfter = computed(() => demo.retryAfter.value);

const inputClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';

function violation(field: string): string | undefined {
  return violations.value.find((v) => v.field === field)?.message;
}

function onSubmit() {
  if (!form.url) return;
  void demo.run({ url: form.url });
}
</script>
