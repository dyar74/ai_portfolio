<template>
  <div class="min-h-screen bg-gray-50 py-12 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-3xl px-4">
      <div class="mb-8 flex items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">
            {{ t('admin.badge') }}
          </p>
          <h1 class="text-3xl font-bold tracking-tight">{{ t('admin.heading') }}</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t('admin.subheading') }}</p>
        </div>
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
          @click="router.push({ name: locale })"
        >
          {{ t('nav.to', { section: t('hero.title') }) }}
        </button>
      </div>

      <div class="flex flex-col gap-6">
        <!-- Bio -->
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ t('admin.about') }}
          </h2>
          <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">{{ t('about.bio') }}</p>
        </section>

        <!-- Hero / tagline -->
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ t('admin.hero') }}
          </h2>
          <dl class="flex flex-col gap-3">
            <div>
              <dt class="text-xs uppercase tracking-wide text-gray-400">{{ t('admin.name') }}</dt>
              <dd class="text-gray-900 dark:text-gray-100">{{ t('hero.name') }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-gray-400">{{ t('admin.title') }}</dt>
              <dd class="text-gray-900 dark:text-gray-100">{{ t('hero.title') }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-gray-400">{{ t('admin.tagline') }}</dt>
              <dd class="text-gray-700 dark:text-gray-300">{{ t('hero.tagline') }}</dd>
            </div>
          </dl>
        </section>

        <!-- Services -->
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ t('admin.services') }}
          </h2>
          <ul class="flex flex-col gap-3">
            <li v-for="(svc, key) in serviceKeys" :key="key" class="flex flex-col gap-1">
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ t(`services.items.${svc}.title`) }}</span>
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ t(`services.items.${svc}.desc`) }}</span>
            </li>
          </ul>
        </section>

        <!-- Tech stack -->
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ t('admin.stack') }}
          </h2>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="(item, key) in stack" :key="key" variant="neutral" class="text-sm">
              {{ item }}
            </Badge>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import Badge from '../components/Badge.vue';

const { t, locale } = useI18n();
const router = useRouter();

const serviceKeys = ['n8n', 'llm', 'agents', 'integrations', 'audit'] as const;

const stack = computed(() =>
  (['symfony', 'php', 'vue', 'ts', 'tailwind', 'n8n', 'llm', 'mysql', 'redis', 'vite'] as const).map(
    (k) => t(`about.stack.items.${k}`),
  ),
);
</script>
