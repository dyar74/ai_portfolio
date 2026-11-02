import { ref, type Ref } from 'vue';
import { api, ApiError } from '../utils/api';

export type DemoStatus = 'idle' | 'loading' | 'success' | 'error';
export type DemoErrorCode =
  | 'validation_failed'
  | 'rate_limited'
  | 'timeout'
  | 'server_error'
  | 'network_error'
  | 'unknown';

export interface Violation {
  field: string;
  message: string;
}

export interface DemoState<T = unknown> {
  status: Ref<DemoStatus>;
  data: Ref<T | null>;
  errorCode: Ref<DemoErrorCode | null>;
  violations: Ref<Violation[]>;
  retryAfter: Ref<number | null>;
  requestId: Ref<string | null>;
  run: (payload: Record<string, unknown>) => Promise<void>;
  reset: () => void;
}

/**
 * Wraps POST /api/demo/{slug} with full state + error mapping.
 * - 422 -> validation_failed (violations)
 * - 429 -> rate_limited (retryAfter)
 * - 504 -> timeout
 * - 5xx -> server_error
 */
export function useDemo<T = unknown>(slug: string): DemoState<T> {
  const status = ref<DemoStatus>('idle');
  const data = ref<T | null>(null) as Ref<T | null>;
  const errorCode = ref<DemoErrorCode | null>(null);
  const violations = ref<Violation[]>([]);
  const retryAfter = ref<number | null>(null);
  const requestId = ref<string | null>(null);

  function reset() {
    status.value = 'idle';
    data.value = null;
    errorCode.value = null;
    violations.value = [];
    retryAfter.value = null;
    requestId.value = null;
  }

  async function run(payload: Record<string, unknown>) {
    reset();
    status.value = 'loading';

    try {
      const res = await api<{ ok: boolean; data: T; requestId: string }>(`/api/demo/${slug}`, {
        method: 'POST',
        body: payload,
      });
      status.value = 'success';
      data.value = res.data as T;
      requestId.value = res.requestId ?? null;
    } catch (e) {
      status.value = 'error';
      if (e instanceof ApiError) {
        if (e.status === 422 && e.body?.violations) {
          errorCode.value = 'validation_failed';
          violations.value = e.body.violations;
        } else if (e.status === 429) {
          errorCode.value = 'rate_limited';
          retryAfter.value = e.retryAfter ?? e.body?.retryAfter ?? null;
        } else if (e.status === 504) {
          errorCode.value = 'timeout';
        } else if (e.status >= 500 && e.status < 600) {
          errorCode.value = 'server_error';
        } else if (e.status === 0) {
          errorCode.value = 'network_error';
        } else {
          errorCode.value = 'unknown';
        }
      } else {
        errorCode.value = 'unknown';
      }
    }
  }

  return {
    status,
    data,
    errorCode,
    violations,
    retryAfter,
    requestId,
    run,
    reset,
  };
}
