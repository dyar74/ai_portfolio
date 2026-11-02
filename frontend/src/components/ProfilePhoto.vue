<template>
  <span
    :class="
      'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md ' +
      sizeClass
    "
    :style="{ width: px + 'px', height: px + 'px' }"
    role="img"
    :aria-label="alt"
  >
    <!-- Monogram fallback (always rendered beneath; image covers it when loaded) -->
    <span class="select-none font-bold" :style="{ fontSize: px * 0.34 + 'px' }" aria-hidden="true">
      YD
    </span>
    <img
      v-if="loaded"
      :src="src"
      :alt="alt"
      loading="lazy"
      decoding="async"
      class="absolute inset-0 h-full w-full object-cover"
      @error="onError"
    />
  </span>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(defineProps<{ size?: number; src?: string }>(), {
  size: 80,
  src: '/images/profile.jpg',
});

const { t } = useI18n();
const alt = t('profile.photoAlt');
const loaded = ref(true);

const px = props.size;
const sizeClass = 'flex';

function onError() {
  // If the photo is missing/broken, fall back to the monogram.
  loaded.value = false;
}
</script>
