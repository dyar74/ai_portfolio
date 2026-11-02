/**
 * Minimal, typed fetch client for the same-origin BFF API.
 * The browser ONLY talks to /api/* — never to n8n directly.
 */

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly body: ApiErrorBody | null = null,
    public readonly retryAfter?: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export interface ApiErrorBody {
  error?: string;
  violations?: Array<{ field: string; message: string }>;
  retryAfter?: number;
}

export interface RequestOptions {
  method?: 'GET' | 'POST';
  body?: unknown;
  headers?: Record<string, string>;
  signal?: AbortSignal;
}

export async function api<T>(path: string, opts: RequestOptions = {}): Promise<T> {
  const init: RequestInit = {
    method: opts.method ?? 'GET',
    headers: { Accept: 'application/json', ...(opts.headers ?? {}) },
    signal: opts.signal,
  };

  if (opts.body !== undefined) {
    (init.headers as Record<string, string>)['Content-Type'] = 'application/json';
    init.body = JSON.stringify(opts.body);
  }

  let res: Response;
  try {
    res = await fetch(path, init);
  } catch {
    throw new ApiError(0, 'network_error', null);
  }

  let parsed: unknown = null;
  const ctype = res.headers.get('Content-Type') ?? '';
  if (ctype.includes('application/json')) {
    try {
      parsed = await res.json();
    } catch {
      parsed = null;
    }
  }

  if (!res.ok) {
    const body = (parsed ?? {}) as ApiErrorBody;
    const retryAfter = res.headers.get('Retry-After')
      ? Number(res.headers.get('Retry-After'))
      : body.retryAfter;
    throw new ApiError(res.status, `http_${res.status}`, body, retryAfter);
  }

  return parsed as T;
}
