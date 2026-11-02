<template>
  <div>
    <div role="tablist" class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        role="tab"
        :aria-selected="modelValue === tab.value"
        :class="tabClass(modelValue === tab.value)"
        @click="emit('update:modelValue', tab.value)"
      >
        <slot :name="`label-${tab.value}`">
          {{ tab.label }}
        </slot>
      </button>
    </div>
    <div role="tabpanel" class="mt-6">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
export interface Tab {
  value: string;
  label: string;
}
defineProps<{ modelValue: string; tabs: Tab[] }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
function tabClass(active: boolean) {
  const base = 'rounded-t-lg px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';
  return active
    ? `${base} border border-b-0 border-gray-200 bg-white text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-blue-400`
    : `${base} border border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200`;
}
</script>
