<template>
  <Badge :variant="isLive ? 'success' : 'danger'" dot>
    <span>{{ isLive ? t('demos.n8nLive') : t('demos.n8nDown') }}</span>
    <span v-if="isLive && showLatency" class="opacity-70">
      {{ t('demos.latency', { ms: latencyMs }) }}
    </span>
  </Badge>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useHealth } from '../stores/health';
import Badge from './Badge.vue';

withDefaults(defineProps<{ showLatency?: boolean }>(), { showLatency: true });
const { t } = useI18n();
const health = useHealth();

const isLive = computed(() => health.isLive);
const latencyMs = computed(() => health.latencyMs);

let started = false;
onMounted(() => {
  if (!started) {
    health.startPolling(30_000);
    started = true;
  }
});
</script>
