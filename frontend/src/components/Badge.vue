<template>
  <span :class="classes" v-bind="$attrs">
    <span v-if="dot" :class="dotClass" class="h-2 w-2 rounded-full" aria-hidden="true"></span>
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

type Variant = 'neutral' | 'success' | 'danger' | 'info' | 'warning';

const props = withDefaults(
  defineProps<{ variant?: Variant; dot?: boolean }>(),
  { variant: 'neutral', dot: false },
);

const classes = computed(() => {
  const base = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium';
  const variants: Record<Variant, string> = {
    neutral: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    warning: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  };
  return `${base} ${variants[props.variant]}`;
});

const dotClass = computed(() => {
  const map: Record<Variant, string> = {
    neutral: 'bg-gray-400',
    success: 'bg-green-500',
    danger: 'bg-red-500',
    info: 'bg-blue-500',
    warning: 'bg-amber-500',
  };
  return map[props.variant];
});
</script>
