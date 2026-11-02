import { describe, it, expect, afterEach } from 'vitest';
import { useDemo } from '../composables/useDemo';

interface MockResponse {
  ok: boolean;
  status: number;
  headers?: Record<string, string>;
  json?: Record<string, unknown>;
}

function mockFetch(response: MockResponse) {
  const headers = response.headers ?? {};
  const mockResponse = {
    ok: response.ok,
    status: response.status,
    headers: {
      get: (name: string) => {
        const lower = name.toLowerCase();
        for (const [key, val] of Object.entries(headers)) {
          if (key.toLowerCase() === lower) return val;
        }
        return null;
      },
    },
    json: async () => response.json ?? {},
  } as unknown as Response;
  return ((_: unknown, __: unknown) => Promise.resolve(mockResponse)) as typeof globalThis.fetch;
}

describe('useDemo composable', () => {
  const originalFetch = globalThis.fetch;
  afterEach(() => {
    globalThis.fetch = originalFetch;
  });

  it('returns success with data on 200', async () => {
    globalThis.fetch = mockFetch({
      ok: true,
      status: 200,
      headers: { 'Content-Type': 'application/json' },
      json: { ok: true, data: { hello: 'world' }, requestId: 'r1' },
    });
    const demo = useDemo<{ hello: string }>('test');
    await demo.run({});
    expect(demo.status.value).toBe('success');
    expect(demo.data.value).toEqual({ hello: 'world' });
  });

  it('maps 422 to validation_failed with violations', async () => {
    globalThis.fetch = mockFetch({
      ok: false,
      status: 422,
      headers: { 'Content-Type': 'application/json' },
      json: { error: 'validation_failed', violations: [{ field: 'name', message: 'required' }] },
    });
    const demo = useDemo('test');
    await demo.run({});
    expect(demo.status.value).toBe('error');
    expect(demo.errorCode.value).toBe('validation_failed');
    expect(demo.violations.value).toHaveLength(1);
  });

  it('maps 429 to rate_limited with retryAfter', async () => {
    globalThis.fetch = mockFetch({
      ok: false,
      status: 429,
      headers: { 'Content-Type': 'application/json', 'Retry-After': '30' },
      json: { error: 'rate_limited' },
    });
    const demo = useDemo('test');
    await demo.run({});
    expect(demo.status.value).toBe('error');
    expect(demo.errorCode.value).toBe('rate_limited');
    expect(demo.retryAfter.value).toBe(30);
  });

  it('maps 504 to timeout', async () => {
    globalThis.fetch = mockFetch({ ok: false, status: 504, headers: { 'Content-Type': 'application/json' }, json: { error: 'demo_timeout' } });
    const demo = useDemo('test');
    await demo.run({});
    expect(demo.errorCode.value).toBe('timeout');
  });

  it('maps 502 to server_error', async () => {
    globalThis.fetch = mockFetch({ ok: false, status: 502, headers: { 'Content-Type': 'application/json' }, json: { error: 'demo_error' } });
    const demo = useDemo('test');
    await demo.run({});
    expect(demo.errorCode.value).toBe('server_error');
  });

  it('reset returns to idle state', async () => {
    globalThis.fetch = mockFetch({ ok: true, status: 200, headers: { 'Content-Type': 'application/json' }, json: { ok: true, data: {} } });
    const demo = useDemo('test');
    await demo.run({});
    expect(demo.status.value).toBe('success');
    demo.reset();
    expect(demo.status.value).toBe('idle');
    expect(demo.data.value).toBeNull();
  });
});
