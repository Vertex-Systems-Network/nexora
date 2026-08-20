export type AsyncStatus = "idle" | "loading" | "success" | "error" | "refreshing";

export type AsyncState<T> =
    | { status: "idle"; data?: undefined; error?: undefined }
    | { status: "loading"; data?: undefined; error?: undefined }
    | { status: "success"; data: T; error?: undefined }
    | { status: "refreshing"; data: T; error?: undefined }
    | { status: "error"; data?: T; error: Error };
