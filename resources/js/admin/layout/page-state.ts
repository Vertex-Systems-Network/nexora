export type PageState =
    | { kind: "ready" }
    | { kind: "loading"; message?: string }
    | { kind: "empty"; title: string; description?: string }
    | { kind: "error"; title: string; description?: string; retryable?: boolean }
    | { kind: "forbidden"; title?: string; description?: string };
