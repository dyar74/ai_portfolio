<template>
  <section id="demos" class="scroll-mt-20 py-16 md:py-24">
    <div class="mx-auto max-w-6xl px-4">
      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <SectionHeading :heading="t('demos.heading')" :subheading="t('demos.subheading')" />
        <HealthBadge />
      </div>

      <div class="mb-8">
        <LiveStats />
      </div>

      <Tabs v-model="active" :tabs="tabs">
        <div v-show="active === 'contact'"><ContactDemo /></div>
        <div v-show="active === 'translate'"><TranslateDemo /></div>
        <div v-show="active === 'summarize'"><SummarizerDemo /></div>
        <div v-show="active === 'score'"><LeadScoringDemo /></div>
      </Tabs>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionHeading from '../SectionHeading.vue';
import HealthBadge from '../HealthBadge.vue';
import LiveStats from '../LiveStats.vue';
import Tabs, { type Tab } from '../Tabs.vue';
import ContactDemo from '../demos/ContactDemo.vue';
import TranslateDemo from '../demos/TranslateDemo.vue';
import SummarizerDemo from '../demos/SummarizerDemo.vue';
import LeadScoringDemo from '../demos/LeadScoringDemo.vue';

const { t } = useI18n();
const active = ref('contact');

const tabs: Tab[] = [
  { value: 'contact', label: t('demoContact.title') },
  { value: 'translate', label: t('demoTranslate.title') },
  { value: 'summarize', label: t('demoSummarize.title') },
  { value: 'score', label: t('demoScore.title') },
];
</script>
