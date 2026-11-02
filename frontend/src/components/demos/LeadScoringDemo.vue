<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Form -->
    <div class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demoScore.title') }}</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('demoScore.desc') }}</p>

      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <label for="score-desc" class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ t('demoScore.description') }}
          </label>
          <textarea
            id="score-desc"
            v-model="form.description"
            rows="5"
            :placeholder="t('demoScore.descPh')"
            :class="inputClass"
          />
        </div>

        <Button :loading="isRunning" :block="true" @click="onSubmit">{{ t('demoScore.title') }}</Button>
      </div>

      <CurlExample :curl="curlExample" />
    </div>

    <!-- Result -->
    <div aria-live="polite" :aria-busy="isRunning" class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demoScore.score') }}</h3>

      <div v-if="isRunning" class="flex items-center gap-2 text-gray-500">
        <Spinner size="sm" /> {{ t('common.loading') }}
      </div>

      <DemoError v-else-if="isError" :error-code="errorCode" :violations="violations" :retry-after="retryAfter" />

      <div v-else-if="isSuccess && result" class="flex flex-col gap-4">
        <!-- Score gauge -->
        <div class="rounded-lg bg-gray-50 p-5 dark:bg-gray-900/40">
          <div class="mb-2 flex items-end justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('demoScore.score') }}</span>
            <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">
              {{ formatNumber(result.score, locale) }}<span class="text-base font-normal text-gray-400">/100</span>
            </span>
          </div>
          <div
            class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
            role="progressbar"
            :aria-valuenow="result.score"
            :aria-valuemin="0"
            :aria-valuemax="100"
            :aria-label="t('demoScore.scoreLabel', { n: result.score })"
          >
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="scoreColor"
              :style="{ width: result.score + '%' }"
            />
          </div>
        </div>

        <div v-if="result.reasons?.length" class="flex flex-col gap-2">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('demoScore.reasons') }}</p>
          <ul class="list-disc space-y-1 pl-5 text-gray-700 dark:text-gray-300">
            <li v-for="(reason, i) in result.reasons" :key="i">{{ reason }}</li>
          </ul>
        </div>

        <div v-if="result.suggestedNextStep" class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
          <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ t('demoScore.nextStep') }}</p>
          <p class="mt-1 text-gray-700 dark:text-gray-300">{{ result.suggestedNextStep }}</p>
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
import { formatNumber } from '../../utils/format';
import Button from '../Button.vue';
import Spinner from '../Spinner.vue';
import DemoError from '../DemoError.vue';
import CurlExample from '../CurlExample.vue';

interface ScoreResult {
  score: number;
  reasons: string[];
  suggestedNextStep: string;
}

const { t, locale } = useI18n();
const curlExample = `
curl -X POST http://localhost:8080/api/demo/lead-scoring
  -H 'Content-Type: application/json'
  -d '{"description":"A 20-person SaaS looking to automate support."}'
`

const form = reactive({ description: '' });
const demo = useDemo<ScoreResult>('lead-scoring');

const isRunning = computed(() => demo.status.value === 'loading');
const isSuccess = computed(() => demo.status.value === 'success');
const isError = computed(() => demo.status.value === 'error');
const result = computed(() => demo.data.value);
const violations = computed<Violation[]>(() => demo.violations.value);
const errorCode = computed(() => demo.errorCode.value);
const retryAfter = computed(() => demo.retryAfter.value);

const inputClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';

const scoreColor = computed(() => {
  const s = result.value?.score ?? 0;
  if (s >= 70) return 'bg-green-500';
  if (s >= 40) return 'bg-amber-500';
  return 'bg-red-500';
});

function onSubmit() {
  if (!form.description) return;
  void demo.run({ description: form.description });
}
</script>
