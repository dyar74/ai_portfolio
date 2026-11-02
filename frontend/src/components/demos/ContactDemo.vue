<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Form -->
    <div class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demoContact.title') }}</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('demoContact.desc') }}</p>

      <form novalidate @submit.prevent="onSubmit" aria-label="contact-form">
        <div class="flex flex-col gap-4">
          <div class="flex flex-col gap-1.5">
            <label for="contact-name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('contact.form.name') }}
            </label>
            <input
              id="contact-name"
              v-model.trim="form.name"
              type="text"
              autocomplete="name"
              :placeholder="t('contact.form.namePh')"
              :class="inputClass('name')"
            />
            <p v-if="violation('name')" role="alert" class="text-sm text-red-600 dark:text-red-400">
              {{ violation('name') }}
            </p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="contact-email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('contact.form.email') }}
            </label>
            <input
              id="contact-email"
              v-model.trim="form.email"
              type="email"
              autocomplete="email"
              :placeholder="t('contact.form.emailPh')"
              :class="inputClass('email')"
            />
            <p v-if="violation('email')" role="alert" class="text-sm text-red-600 dark:text-red-400">
              {{ violation('email') }}
            </p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="contact-message" class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('contact.form.message') }}
            </label>
            <textarea
              id="contact-message"
              v-model="form.message"
              rows="4"
              :placeholder="t('contact.form.messagePh')"
              :class="inputClass('message')"
            />
            <p v-if="violation('message')" role="alert" class="text-sm text-red-600 dark:text-red-400">
              {{ violation('message') }}
            </p>
          </div>

          <!-- Honeypot -->
          <input type="text" name="website" v-model="form.website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden" />

          <Button type="submit" :loading="isRunning" :block="true">{{ t('contact.form.submit') }}</Button>
        </div>
      </form>

      <CurlExample :curl="curlExample" />
    </div>

    <!-- Result / timeline -->
    <div aria-live="polite" :aria-busy="isRunning" class="flex flex-col gap-4">
      <h3 class="text-lg font-semibold">{{ t('demos.heading') }}</h3>

      <div v-if="isRunning" class="flex items-center gap-2 text-gray-500">
        <Spinner size="sm" /> {{ t('common.loading') }}
      </div>

      <DemoError v-else-if="isError" :error-code="errorCode" :violations="violations" :retry-after="retryAfter" />

      <div v-else-if="isSuccess && result" class="flex flex-col gap-4">
        <div v-if="result.category === 'spam'" class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
          <p class="font-medium text-amber-800 dark:text-amber-200">{{ t('demo.spam') }}</p>
        </div>

        <ol class="flex flex-col gap-3">
          <li v-for="step in timeline" :key="step.key" class="flex items-start gap-3">
            <span
              :class="
                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold ' +
                (step.state === 'done'
                  ? 'bg-green-500 text-white'
                  : step.state === 'active'
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-200 text-gray-500 dark:bg-gray-700')
              "
              aria-hidden="true"
            >
              {{ step.state === 'done' ? '✓' : step.state === 'active' ? '…' : '•' }}
            </span>
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ step.label }}</p>
              <p v-if="step.detail" class="text-sm text-gray-600 dark:text-gray-400">{{ step.detail }}</p>
            </div>
          </li>
        </ol>

        <div class="flex flex-wrap gap-2">
          <Badge variant="info">{{ t(`demoContact.category.${result.category}`) }}</Badge>
          <Badge variant="neutral">{{ t('demoContact.priority', { n: result.priority }) }}</Badge>
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
import { useLocale } from '../../stores/locale';
import Button from '../Button.vue';
import Spinner from '../Spinner.vue';
import Badge from '../Badge.vue';
import DemoError from '../DemoError.vue';
import CurlExample from '../CurlExample.vue';

interface ContactResult {
  ok: boolean;
  category: 'sales' | 'support' | 'spam';
  priority: number;
}

const { t } = useI18n();
const localeStore = useLocale();

const curlExample = `
curl -X POST http://localhost:8080/api/demo/contact-to-telegram
  -H 'Content-Type: application/json'
  -d '{"name":"Jane","email":"jane@example.com","message":"I need help with CRM","locale":"en"}'
`

const form = reactive({ name: '', email: '', message: '', website: '' });
const demo = useDemo<ContactResult>('contact-to-telegram');

const isRunning = computed(() => demo.status.value === 'loading');
const isSuccess = computed(() => demo.status.value === 'success');
const isError = computed(() => demo.status.value === 'error');
const result = computed(() => demo.data.value);
const violations = computed<Violation[]>(() => demo.violations.value);
const errorCode = computed(() => demo.errorCode.value);
const retryAfter = computed(() => demo.retryAfter.value);

function violation(field: string): string | undefined {
  return violations.value.find((v) => v.field === field)?.message;
}

function inputClass(field: string): string {
  const base =
    'w-full rounded-lg border px-3 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:bg-gray-900 dark:text-gray-100';
  return violation(field)
    ? `${base} border-red-400 dark:border-red-700`
    : `${base} border-gray-300 dark:border-gray-700`;
}

function onSubmit() {
  if (!form.name || !form.email || !form.message) return;
  void demo.run({
    name: form.name,
    email: form.email,
    message: form.message,
    locale: localeStore.current(),
    website: form.website,
  });
}

const timeline = computed(() => {
  const r = result.value;
  return [
    { key: 'received', label: t('demo.steps.received'), state: 'done' as const },
    {
      key: 'classified',
      label: t('demo.steps.classified'),
      state: (r?.category ? 'done' : 'active') as 'done' | 'active',
      detail: r?.category ? `${t(`demoContact.category.${r.category}`)} · ${t('demoContact.priority', { n: r.priority })}` : '',
    },
    { key: 'notified', label: t('demo.steps.notified'), state: (r && r.category !== 'spam' ? 'done' : 'active') as 'done' | 'active' },
    { key: 'replied', label: t('demo.steps.replied'), state: (r && r.category !== 'spam' ? 'done' : 'active') as 'done' | 'active' },
  ];
});
</script>
