import { createServer } from 'vite';
const s = await createServer({ server: { middlewareMode: true }, appType: 'custom', logLevel: 'silent' });
try {
  const i18nMod = await s.ssrLoadModule('/src/i18n/index.ts');
  const inst = await i18nMod.createI18nInstance();
  const { useLocale } = await s.ssrLoadModule('/src/stores/locale.ts');

  // Access current() OUTSIDE any reactive effect (like a one-shot render read).
  const loc = useLocale();
  console.log('outside-effect: initial current() =', loc.current());

  inst.global.locale.value = 'uk';
  console.log('outside-effect: after set uk, current() =', loc.current(), '(reactive? should be uk)');

  // Now inside a reactive effect (computed/watch) — does it track?
  const { reactive, computed } = await s.ssrLoadModule('/node_modules/vue/dist/vue.esm-bundler.js').catch(() => import('vue'));
  void reactive;
  const vue = await import('vue');
  const state = vue.reactive({ v: loc.current() });
  console.log('reactive snapshot v =', state.v);

  // Use a computed to see if it tracks global.locale
  const c = vue.computed(() => loc.current());
  inst.global.locale.value = 'en';
  console.log('computed after set en =', c.value, '(should be en if reactive)');
  inst.global.locale.value = 'uk';
  console.log('computed after set uk =', c.value, '(should be uk if reactive)');

  // The critical test: does a Vue component's render (which reads loc.current())
  // re-run when global.locale changes? Simulate by reading inside effectScope.
  const scope = vue.effectScope();
  let reads = 0;
  scope.run(() => {
    vue.watch(
      () => loc.current(),
      () => { reads++; },
      { immediate: false },
    );
  });
  inst.global.locale.value = 'en';
  await new Promise((r) => setTimeout(r, 20));
  inst.global.locale.value = 'uk';
  await new Promise((r) => setTimeout(r, 20));
  console.log('watch fired', reads, 'time(s) — should be 2 if loc.current() is reactive');

  console.log('\nRESULT:', reads === 2 ? 'PASS — useLocale().current() IS reactive' : 'FAIL — not reactive (this explains the bug)');
  if (reads !== 2) process.exitCode = 1;
} catch (e) {
  console.error('ERR:', (e && e.stack) || e);
  process.exitCode = 1;
} finally {
  await s.close();
}
