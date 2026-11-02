<template>
  <section id="hero" class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950" />
    <div class="mx-auto flex max-w-5xl flex-col items-center gap-6 px-4 py-20 text-center md:py-28">
      <ProfilePhoto :size="128" class="ring-4 ring-white dark:ring-gray-900" />

      <div class="flex flex-col items-center gap-1">
        <p class="text-lg text-gray-500 dark:text-gray-400">
          {{ t('hero.greeting') }}
        </p>
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100 md:text-6xl">
          {{ t('hero.name') }}
        </h1>
        <p class="text-xl font-semibold text-blue-600 dark:text-blue-400 md:text-2xl">
          {{ t('hero.title') }}
        </p>
      </div>

      <p class="max-w-2xl text-lg text-gray-600 dark:text-gray-400">{{ t('hero.tagline') }}</p>
      <p class="text-sm text-gray-500 dark:text-gray-400">📍 {{ t('hero.location') }}</p>

      <div class="mt-2 flex flex-wrap justify-center gap-3">
        <Button size="lg" @click="scrollTo('demos')">{{ t('hero.ctaDemo') }}</Button>
        <Button size="lg" variant="secondary" @click="scrollTo('contact')">{{ t('hero.ctaContact') }}</Button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Button from '../Button.vue';
import ProfilePhoto from '../ProfilePhoto.vue';

const { t } = useI18n();

function scrollTo(id: string) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
