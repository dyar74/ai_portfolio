import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api, ApiError } from '../utils/api';

interface Health {
  site: string;
  n8n: 'ok' | 'down';
  latencyMs: number;
}

export const useHealth = defineStore('health', () => {
  const data = ref<Health | null>(null);
  const loading = ref(false);
  const error = ref(false);

  const isLive = computed(() => data.value?.n8n === 'ok');
  const latencyMs = computed(() => data.value?.latencyMs ?? 0);

  async function refresh() {
    loading.value = true;
    try {
      data.value = await api<Health>('/api/health');
      error.value = false;
    } catch (e) {
      if (e instanceof ApiError) {
        // n8n may be down; keep last known, mark error
        error.value = true;
        if (!data.value) {
          data.value = { site: 'ok', n8n: 'down', latencyMs: 0 };
        }
      } else {
        error.value = true;
      }
    } finally {
      loading.value = false;
    }
  }

  let timer: ReturnType<typeof setInterval> | null = null;
  function startPolling(ms = 30_000) {
    if (timer) clearInterval(timer);
    void refresh();
    timer = setInterval(() => void refresh(), ms);
  }
  function stopPolling() {
    if (timer) clearInterval(timer);
    timer = null;
  }

  return { data, loading, error, isLive, latencyMs, refresh, startPolling, stopPolling };
});
