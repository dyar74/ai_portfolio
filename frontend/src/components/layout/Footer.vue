<template>
  <footer class="border-t border-gray-200 bg-white py-8 dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 sm:flex-row">
      <div class="flex items-center gap-4">
        <HealthBadge :show-latency="false" />
        <a
          v-for="link in socialLinks"
          :key="link.key"
          :href="link.href"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="link.label"
          class="text-gray-500 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
        >
          <span class="text-lg" aria-hidden="true">{{ link.icon }}</span>
        </a>
      </div>

      <div class="flex flex-col items-center gap-1 sm:items-end">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ t('footer.rights', { year: new Date().getFullYear() }) }}
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ t('footer.builtWith') }}</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import HealthBadge from '../HealthBadge.vue';
import { SITE } from '../../../site.defaults';

const { t } = useI18n();
const socialLinks = [
  { key: 'email', icon: '✉️', label: t('contact.links.email'), href: `mailto:${SITE.email}` },
  { key: 'telegram', icon: '✈️', label: t('contact.links.telegram'), href: SITE.telegramUrl },
  { key: 'linkedin', icon: '💼', label: t('contact.links.linkedin'), href: SITE.linkedinUrl },
  { key: 'github', icon: '🐙', label: t('contact.links.github'), href: SITE.githubUrl },
];
</script>
