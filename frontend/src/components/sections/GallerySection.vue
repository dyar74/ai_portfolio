<template>
  <section id="gallery" class="scroll-mt-20 bg-gray-50 py-16 dark:bg-gray-900/40 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <SectionHeading :heading="t('gallery.heading')" :subheading="t('gallery.subheading')" />
      <WorkflowGallery />
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import SectionHeading from '../SectionHeading.vue';
import WorkflowGallery from '../WorkflowGallery.vue';

const { t } = useI18n();
</script>
