import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

type Theme = 'light' | 'dark';

const STORAGE_KEY = 'portfolio.theme';

function systemPref(): Theme {
  if (typeof window === 'undefined' || !window.matchMedia) return 'light';
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function initial(): Theme {
  if (typeof localStorage === 'undefined') return systemPref();
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'light' || stored === 'dark') return stored;
  return systemPref();
}

export const useTheme = defineStore('theme', () => {
  const theme = ref<Theme>(initial());

  function apply(t: Theme) {
    const root = document.documentElement;
    root.classList.toggle('dark', t === 'dark');
    root.style.colorScheme = t;
  }

  // Apply on load
  if (typeof document !== 'undefined') {
    apply(theme.value);
  }

  watch(
    theme,
    (t) => {
      apply(t);
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, t);
      }
    },
    { immediate: false },
  );

  const isDark = computed(() => theme.value === 'dark');
  const toggle = () => {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
  };

  return { theme, isDark, toggle };
});
