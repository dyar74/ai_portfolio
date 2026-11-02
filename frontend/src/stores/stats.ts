import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../utils/api';

export interface DemoStats {
  runs: number;
  avgDurationMs: number;
  successRate: number;
}

export interface StatsPayload {
  stats: Record<string, DemoStats>;
  timestamp?: string;
}

export const useStats = defineStore('stats', () => {
  const data = ref<StatsPayload | null>(null);
  const loading = ref(false);
  const error = ref(false);

  const hasData = computed(() => {
    if (!data.value?.stats) return false;
    return Object.values(data.value.stats).some((s) => s.runs > 0);
  });

  async function refresh() {
    loading.value = true;
    try {
      data.value = await api<StatsPayload>('/api/stats');
      error.value = false;
    } catch {
      error.value = true;
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, hasData, refresh };
});
