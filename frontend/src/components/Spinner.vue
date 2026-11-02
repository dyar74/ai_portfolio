<template>
  <span
    :class="classes"
    role="status"
    aria-label="loading"
  >
    <span class="h-full w-full animate-spin rounded-full border-4 border-current border-t-transparent" />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' });

const classes = computed(() => {
  const base = 'inline-block';
  const sizes = { sm: 'h-4 w-4', md: 'h-6 w-6', lg: 'h-10 w-10' }[props.size];
  return `${base} ${sizes}`;
});
</script>
