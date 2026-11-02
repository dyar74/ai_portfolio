import { describe, it, expect, beforeEach } from 'vitest';
import { nextTick } from 'vue';
import { setActivePinia, createPinia } from 'pinia';
import { useTheme } from '../stores/theme';

describe('theme store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
  });

  it('toggle flips the dark class on <html>', async () => {
    const theme = useTheme();
    const initial = theme.isDark;
    theme.toggle();
    expect(theme.isDark).toBe(!initial);
    await nextTick();
    expect(document.documentElement.classList.contains('dark')).toBe(theme.isDark);
  });

  it('persists the chosen theme to localStorage', async () => {
    const theme = useTheme();
    theme.toggle();
    await nextTick();
    const stored = localStorage.getItem('portfolio.theme');
    expect(stored).toBe(theme.theme);
  });
});
