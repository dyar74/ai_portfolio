<template>
  <section id="about" class="scroll-mt-20 bg-gray-50 py-16 dark:bg-gray-900/40 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <SectionHeading :heading="t('about.heading')" />
      <div class="grid gap-8 lg:grid-cols-[auto_1fr] lg:items-start">
        <div class="flex justify-center lg:justify-start">
          <ProfilePhoto :size="180" class="ring-4 ring-white dark:ring-gray-800" />
        </div>

        <div class="flex flex-col gap-6">
          <div class="flex flex-col gap-4">
            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">{{ t('about.bio') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              📍 {{ t('hero.location') }} · 🌐 {{ t('profile.domain') }}
            </p>
          </div>

          <div>
            <h3 class="mb-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
              {{ t('about.stack.heading') }}
            </h3>
            <div class="flex flex-wrap gap-2">
              <Badge v-for="(item, key) in stack" :key="key" variant="neutral" class="text-sm">
                {{ item }}
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionHeading from '../SectionHeading.vue';
import Badge from '../Badge.vue';
import ProfilePhoto from '../ProfilePhoto.vue';

const { t } = useI18n();
const stack = computed(() =>
  (['symfony', 'php', 'vue', 'ts', 'tailwind', 'n8n', 'llm', 'mysql', 'redis', 'vite'] as const).map(
    (k) => t(`about.stack.items.${k}`),
  ),
);
</script>
