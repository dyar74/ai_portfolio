<template>
  <div :class="classes" v-bind="$attrs">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ padded?: boolean }>(), { padded: true });

const classes = computed(
  () =>
    `rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 ${
      props.padded ? 'p-6' : ''
    }`,
);
</script>
