<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Form -->
    <div class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demoTranslate.title') }}</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('demoTranslate.desc') }}</p>

      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <label for="translate-text" class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ t('demoTranslate.text') }}
          </label>
          <textarea
            id="translate-text"
            v-model="form.text"
            rows="5"
            maxlength="1000"
            :class="inputClass"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ t('common.charactersLeft', { n: remaining, word: remainingWord }) }}
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="flex flex-col gap-1.5">
            <label for="translate-direction" class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('demoTranslate.direction') }}
            </label>
            <select id="translate-direction" v-model="form.direction" :class="selectClass">
              <option value="en-uk">{{ t('demoTranslate.dirEnUk') }}</option>
              <option value="uk-en">{{ t('demoTranslate.dirUkEn') }}</option>
            </select>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="translate-tone" class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('demoTranslate.tone') }}
            </label>
            <select id="translate-tone" v-model="form.tone" :class="selectClass">
              <option value="neutral">{{ t('demoTranslate.toneNeutral') }}</option>
              <option value="formal">{{ t('demoTranslate.toneFormal') }}</option>
              <option value="casual">{{ t('demoTranslate.toneCasual') }}</option>
            </select>
          </div>
        </div>

        <Button :loading="isRunning" :block="true" @click="onSubmit">{{ t('demoTranslate.translate') }}</Button>
      </div>

      <CurlExample :curl="curlExample" />
    </div>

    <!-- Result -->
    <div aria-live="polite" :aria-busy="isRunning" class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demoTranslate.result') }}</h3>

      <div v-if="isRunning" class="flex items-center gap-2 text-gray-500">
        <Spinner size="sm" /> {{ t('common.loading') }}
      </div>

      <DemoError v-else-if="isError" :error-code="errorCode" :violations="violations" :retry-after="retryAfter" />

      <div v-else-if="isSuccess && result" class="flex flex-col gap-4">
        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
          <p class="whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ result.translation }}</p>
        </div>

        <div v-if="result.detectedTerms?.length" class="flex flex-col gap-2">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('demoTranslate.terms') }}</p>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="term in result.detectedTerms" :key="term" variant="neutral">{{ term }}</Badge>
          </div>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ t('demoTranslate.tokens', { n: result.tokensUsed ?? 0 }) }}
        </p>
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
import { useLocale } from '../../stores/locale';
import Button from '../Button.vue';
import Spinner from '../Spinner.vue';
import Badge from '../Badge.vue';
import DemoError from '../DemoError.vue';
import CurlExample from '../CurlExample.vue';

interface TranslateResult {
  translation: string;
  detectedTerms: string[];
  tokensUsed: number;
}

const { t } = useI18n();
const localeStore = useLocale();

const curlExample = `
curl -X POST http://localhost:8080/api/demo/ai-translate-en-uk
  -H 'Content-Type: application/json'
  -d '{"text":"Hello, how are you?","direction":"en-uk","tone":"neutral"}'
`

const form = reactive({
  text: '',
  direction: 'en-uk' as 'en-uk' | 'uk-en',
  tone: 'neutral' as 'neutral' | 'formal' | 'casual',
});

const demo = useDemo<TranslateResult>('ai-translate-en-uk');

const isRunning = computed(() => demo.status.value === 'loading');
const isSuccess = computed(() => demo.status.value === 'success');

const MAX_CHARS = 1000;
const remaining = computed(() => MAX_CHARS - form.text.length);
const remainingWord = computed(() =>
  localeStore.current() === 'uk'
    ? remaining.value === 1
      ? 'символ'
      : 'символи'
    : remaining.value === 1
      ? 'character'
      : 'characters',
);
const isError = computed(() => demo.status.value === 'error');
const result = computed(() => demo.data.value);
const violations = computed<Violation[]>(() => demo.violations.value);
const errorCode = computed(() => demo.errorCode.value);
const retryAfter = computed(() => demo.retryAfter.value);

const inputClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';

function onSubmit() {
  if (!form.text) return;
  void demo.run({ text: form.text, direction: form.direction, tone: form.tone });
}
</script>
