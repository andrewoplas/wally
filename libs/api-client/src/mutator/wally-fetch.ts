export interface WallyFetchConfig {
  siteId: string;
  apiKey: string;
  baseUrl?: string;
}

let _config: WallyFetchConfig = {
  siteId: '',
  apiKey: '',
  baseUrl: 'http://localhost:3000/api',
};

/** Call once at app bootstrap to set auth credentials. */
export function configureWallyClient(config: WallyFetchConfig): void {
  _config = { ..._config, ...config };
}

/**
 * Custom Orval mutator.
 * Orval calls this with (url, options) where options contains method, body, headers, signal.
 * We inject the base URL and auth headers before every request.
 */
export async function wallyFetch<T>(
  url: string,
  options?: RequestInit,
): Promise<T> {
  const fullUrl = `${_config.baseUrl}${url}`;
  const response = await fetch(fullUrl, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-Site-ID': _config.siteId,
      'X-API-Key': _config.apiKey,
      ...options?.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`Wally API error: ${response.status} ${response.statusText}`);
  }

  return response.json() as Promise<T>;
}

export default wallyFetch;
