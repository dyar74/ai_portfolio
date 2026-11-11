import type { Plugin } from 'vite';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { baseCompile } from '@intlify/message-compiler';

/**
 * Pre-compile i18n message JSONs into message FUNCTIONS at build time.
 *
 * ROOT CAUSE of the literal `{ms} ms` / `{n} tokens` bug:
 *   vite.config.ts pins 'vue-i18n' to the RUNTIME-ONLY bundle (CSP forbids
 *   'unsafe-eval', so no `new Function()` in the browser). The runtime bundle
 *   registers NO message compiler. When t() gets a message that is a plain
 *   STRING with no compiler, @intlify/core-base does:
 *
 *       if (messageCompiler == null) { const msg = (() => format); return msg; }
 *
 *   → it returns the RAW template string, so t('demos.latency',{ms:12})
 *     yields "{ms} ms" instead of "12 ms".
 *
 * FIX:
 *   Pre-compile every message to its MESSAGE-FUNCTION form once, at build time
 *   (Node, where the compiler is available). At runtime the bundle detects a
 *   function (isMessageFunction) and calls it with the interpolation context —
 *   no `new Function()` in the browser. This is exactly what the vue-i18n
 *   runtime-only docs require ("You need to pre-compile all message format").
 *
 * HOW:
 *   `baseCompile(message, ctx)` returns `{ code }` where `code` is valid JS
 *   source like:  function __msg__(ctx) { const {named:_n}=ctx; return _n("ms")+" ms" }
 *   We emit that source DIRECTLY as the message value in the bundle (no
 *   `new Function`, no JSON.stringify — both of which broke earlier drafts).
 *   A resolveId/load virtual-module pair (enforce:'pre') intercepts the
 *   `../locales/{lang}.json` import and serves a JS module whose named exports
 *   are these pre-compiled message functions — the same shape Vite's JSON
 *   import would produce, so i18n/index.ts's Object.entries(mod) loop works.
 */
export function i18nPrecompilePlugin(localesDir: string): Plugin {
  const dir = fileURLToPath(new URL(localesDir, import.meta.url));
  const ctx = { onError: () => {}, onWarn: () => {} };
  const VIRT = '\0i18n-precompiled:';

  // A single message string → valid JS source of its message function.
  function msgJs(message: string): string {
    const { code } = baseCompile(message, ctx);
    // `code` is already "function __msg__ (ctx) { ... }" — a valid expression.
    return `(${code})`;
  }

  // Recursively turn a message tree into valid JS source.
  function toJs(node: unknown): string {
    if (typeof node === 'string') return msgJs(node);
    if (Array.isArray(node)) return `[${node.map(toJs).join(',')}]`;
    if (node && typeof node === 'object') {
      const entries = Object.entries(node as Record<string, unknown>)
        .map(([k, v]) => `${JSON.stringify(k)}:${toJs(v)}`)
        .join(',');
      return `{${entries}}`;
    }
    return JSON.stringify(node);
  }

  return {
    name: 'i18n-precompile',
    enforce: 'pre',
    resolveId(id, _importer, options) {
      if (id.startsWith(VIRT)) return null;
      if (!/locales\/[a-z]{2}\.json$/.test(id)) return null;
      if (options?.isEntry) return null;
      const lang = id.match(/locales\/([a-z]{2})\.json/)![1];
      return VIRT + lang;
    },
    load(id) {
      if (!id.startsWith(VIRT)) return null;
      const lang = id.slice(VIRT.length);
      const raw = JSON.parse(readFileSync(join(dir, lang + '.json'), 'utf8'));

      const parts: string[] = ['"use strict";'];
      for (const [k, v] of Object.entries(raw as Record<string, unknown>)) {
        parts.push(`exports[${JSON.stringify(k)}] = ${toJs(v)};`);
      }
      parts.push(`exports.default = ${toJs(raw)};`);
      return { code: parts.join('\n') + '\n', map: null };
    },
  };
}
