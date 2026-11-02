<template>
  <section id="contact" class="scroll-mt-20 py-16 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <SectionHeading :heading="t('contact.heading')" :subheading="t('contact.subheading')" />
      <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <ContactDemo />
        </div>

        <div class="flex flex-col gap-4">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ t('contact.links.heading') }}
          </h3>
          <div class="flex flex-col gap-2">
            <a
              v-for="link in links"
              :key="link.key"
              :href="link.href"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/50"
            >
              <span class="text-lg" aria-hidden="true">{{ link.icon }}</span>
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ link.label }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ link.value }}</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import SectionHeading from '../SectionHeading.vue';
import ContactDemo from '../demos/ContactDemo.vue';

const { t } = useI18n();
const links = [
  { key: 'email', icon: '✉️', label: t('contact.links.email'), value: 'info@novotek.com.ua', href: 'mailto:info@novotek.com.ua' },
  { key: 'telegram', icon: '✈️', label: t('contact.links.telegram'), value: '@dyar74', href: 'https://t.me/dyar74' },
  { key: 'linkedin', icon: '💼', label: t('contact.links.linkedin'), value: 'linkedin.com/in/duplyak', href: 'https://www.linkedin.com/in/duplyak/' },
  { key: 'github', icon: '🐙', label: t('contact.links.github'), value: 'github.com/dyar74', href: 'https://github.com/dyar74/' },
];
</script>
