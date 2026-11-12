<template>
  <section id="services" class="scroll-mt-20 py-16 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <SectionHeading :heading="t('services.heading')" :subheading="t('services.subheading')" />
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card v-for="(item, key) in items" :key="key" class="transition-transform hover:-translate-y-1">
          <h3 class="mb-2 text-center text-lg font-semibold text-gray-900 dark:text-gray-100">{{ item.title }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ item.desc }}</p>
        </Card>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '../Card.vue';
import SectionHeading from '../SectionHeading.vue';

const { t } = useI18n();
const items = computed(() =>
  (['n8n', 'llm', 'agents', 'integrations', 'audit'] as const).map((k) => ({
    key: k,
    title: t(`services.items.${k}.title`),
    desc: t(`services.items.${k}.desc`),
  })),
);
</script>
