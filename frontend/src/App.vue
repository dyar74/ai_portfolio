<template>
  <div class="flex min-h-screen flex-col bg-gray-50 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
    <a
      href="#main"
      class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-blue-600 focus:px-4 focus:py-2 focus:text-white"
    >
      {{ t('nav.skipToContent') }}
    </a>

    <Header />

    <main id="main" class="flex-1">
      <router-view />
    </main>

    <Footer />
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Header from './components/layout/Header.vue';
import Footer from './components/layout/Footer.vue';

const { t } = useI18n();
</script>
