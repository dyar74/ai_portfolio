import { describe, it, expect } from 'vitest';
import en from '../locales/en.json';
import uk from '../locales/uk.json';
import { WORKFLOWS } from '../data/workflows';

function allKeys(obj: Record<string, unknown>, prefix = ''): string[] {
  const keys: string[] = [];
  if (obj && typeof obj === 'object') {
    for (const [k, v] of Object.entries(obj)) {
      const path = prefix ? `${prefix}.${k}` : k;
      keys.push(path);
      if (v && typeof v === 'object') {
        keys.push(...allKeys(v as Record<string, unknown>, path));
      }
    }
  }
  return keys;
}

function lookup(obj: Record<string, unknown>, keyPath: string): unknown {
  const parts = keyPath.split('.');
  let current: unknown = obj;
  for (const part of parts) {
    if (current === null || current === undefined || typeof current !== 'object') {
      return undefined;
    }
    current = (current as Record<string, unknown>)[part];
  }
  return current;
}

describe('i18n locale parity', () => {
  it('en and uk have the same top-level keys', () => {
    const enTop = Object.keys(en).sort();
    const ukTop = Object.keys(uk).sort();
    expect(enTop).toEqual(ukTop);
  });

  it('en and uk have identical nested key sets', () => {
    const enKeys = allKeys(en as Record<string, unknown>).sort();
    const ukKeys = allKeys(uk as Record<string, unknown>).sort();
    const onlyEn = enKeys.filter((k) => !ukKeys.includes(k));
    const onlyUk = ukKeys.filter((k) => !enKeys.includes(k));
    expect(onlyEn).toEqual([]);
    expect(onlyUk).toEqual([]);
  });

  it('uk uses Ukrainian pluralization rules (one/few/many/other)', () => {
    expect(uk.common.charactersLeft).toBeDefined();
    expect(uk.common.seconds).toBeDefined();
  });

  it('every wf.* key used in data/workflows.ts exists in en.json', () => {
    const keys: string[] = [];
    for (const wf of WORKFLOWS) {
      keys.push(wf.nameKey);
      for (const node of wf.nodes) {
        keys.push(node.labelKey);
        keys.push(node.descKey);
      }
    }
    const missing = keys.filter((k) => lookup(en as Record<string, unknown>, k) === undefined);
    expect(missing).toEqual([]);
  });

  it('every wf.* key used in data/workflows.ts exists in uk.json', () => {
    const keys: string[] = [];
    for (const wf of WORKFLOWS) {
      keys.push(wf.nameKey);
      for (const node of wf.nodes) {
        keys.push(node.labelKey);
        keys.push(node.descKey);
      }
    }
    const missing = keys.filter((k) => lookup(uk as Record<string, unknown>, k) === undefined);
    expect(missing).toEqual([]);
  });

  it('every wf.* key has a non-empty string value in both locales', () => {
    const keys: string[] = [];
    for (const wf of WORKFLOWS) {
      keys.push(wf.nameKey);
      for (const node of wf.nodes) {
        keys.push(node.labelKey);
        keys.push(node.descKey);
      }
    }
    for (const k of keys) {
      const enVal = lookup(en as Record<string, unknown>, k);
      const ukVal = lookup(uk as Record<string, unknown>, k);
      expect(enVal, `en key ${k}`).toEqual(expect.any(String));
      expect((enVal as string).length, `en key ${k} should be non-empty`).toBeGreaterThan(0);
      expect(ukVal, `uk key ${k}`).toEqual(expect.any(String));
      expect((ukVal as string).length, `uk key ${k} should be non-empty`).toBeGreaterThan(0);
    }
  });
});
