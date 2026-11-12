<template>
  <div class="mb-8 text-center">
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 md:text-4xl">
      {{ heading }}
    </h2>
    <p v-if="subheading" class="mx-auto mt-2 max-w-2xl text-base text-gray-600 dark:text-gray-400">
      {{ subheading }}
    </p>
  </div>
</template>

<script setup lang="ts">
defineProps<{ heading: string; subheading?: string }>();
</script>
