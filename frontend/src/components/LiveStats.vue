<template>
  <Card class="p-5">
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ t('demos.stats.heading') }}
      </h3>
      <span class="text-xs text-gray-400">{{ t('demos.stats.last7d') }}</span>
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-gray-500">
      <Spinner size="sm" /> {{ t('common.loading') }}
    </div>

    <div v-else-if="!hasData" class="text-sm text-gray-500 dark:text-gray-400">
      {{ t('demos.stats.empty') }}
    </div>

    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div
        v-for="(stat, slug) in stats"
        :key="slug"
        class="flex flex-col gap-1 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/40"
      >
        <span class="truncate text-xs font-medium text-gray-500 dark:text-gray-400" :title="slug">
          {{ slug }}
        </span>
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ formatNumber(stat.runs, locale) }}
          <span class="text-xs font-normal text-gray-500">{{ t('demos.stats.runs') }}</span>
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
          {{ t('demos.stats.avgLatency') }}: {{ stat.avgDurationMs }} {{ t('demos.stats.ms') }}
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
          {{ t('demos.stats.success') }}: {{ formatPercent(stat.successRate, locale) }}
        </span>
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useStats } from '../stores/stats';
import { formatNumber, formatPercent } from '../utils/format';
import Card from './Card.vue';
import Spinner from './Spinner.vue';

const { t, locale } = useI18n();
const statsStore = useStats();

const loading = computed(() => statsStore.loading);
const hasData = computed(() => statsStore.hasData);
const stats = computed(() => statsStore.data?.stats ?? {});

onMounted(() => {
  void statsStore.refresh();
});
</script>
