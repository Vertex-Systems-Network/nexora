let installedGeneration: string | null = null;
let nativeFetch: typeof window.fetch | null = null;

export function installDeploymentFetchFence(generation?: string | null): void {
    if (typeof window === "undefined" || !generation) return;
    if (installedGeneration === generation) return;

    if (nativeFetch === null) nativeFetch = window.fetch.bind(window);
    const fetchImpl = nativeFetch;
    installedGeneration = generation;

    window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
        const requestUrl = input instanceof Request ? input.url : String(input);
        const resolved = new URL(requestUrl, window.location.href);
        if (resolved.origin !== window.location.origin) return fetchImpl(input, init);

        const headers = new Headers(input instanceof Request ? input.headers : undefined);
        new Headers(init?.headers).forEach((value, key) => headers.set(key, value));
        headers.set("X-Nexora-Deployment-Generation", generation);

        const response = await fetchImpl(input, { ...init, headers });
        if (response.status === 409 && response.headers.get("X-Nexora-Deployment-Mismatch") === "1") {
            window.location.reload();
        }
        return response;
    };
}
