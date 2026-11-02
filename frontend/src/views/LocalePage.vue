<template>
  <div>
    <Hero />
    <Services />
    <DemosSection />
    <GallerySection />
    <Process />
    <About />
    <ContactSection />
  </div>
</template>

<script setup lang="ts">
defineProps<{ locale: 'en' | 'uk' }>();

import Hero from '../components/sections/Hero.vue';
import Services from '../components/sections/Services.vue';
import DemosSection from '../components/sections/DemosSection.vue';
import GallerySection from '../components/sections/GallerySection.vue';
import Process from '../components/sections/Process.vue';
import About from '../components/sections/About.vue';
import ContactSection from '../components/sections/ContactSection.vue';
</script>
