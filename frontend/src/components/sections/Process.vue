<template>
  <section id="process" class="scroll-mt-20 py-16 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <SectionHeading :heading="t('process.heading')" :subheading="t('process.subheading')" />
      <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="(step, index) in steps" :key="step.key" class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
          <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
            {{ index + 1 }}
          </span>
          <h3 class="text-center text-base font-semibold text-gray-900 dark:text-gray-100">{{ step.title }}</h3>
          <p class="text-center text-sm text-gray-600 dark:text-gray-400">{{ step.desc }}</p>
        </li>
      </ol>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionHeading from '../SectionHeading.vue';

const { t } = useI18n();
const steps = computed(() =>
  (['audit', 'prototype', 'production', 'support'] as const).map((k) => ({
    key: k,
    title: t(`process.steps.${k}.title`),
    desc: t(`process.steps.${k}.desc`),
  })),
);
</script>
