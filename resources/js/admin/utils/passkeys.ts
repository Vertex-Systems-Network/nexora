/**
 * Browser side of SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: converts the server's WebAuthn JSON options
 * to the binary shapes navigator.credentials expects, and the resulting credential back to JSON.
 */

type JsonObject = Record<string, unknown>;

export function passkeysSupported(): boolean {
    return typeof window !== "undefined" && typeof window.PublicKeyCredential === "function" && !!navigator.credentials;
}

export function base64UrlToBuffer(value: string): ArrayBuffer {
    const base64 = value.replace(/-/g, "+").replace(/_/g, "/").padEnd(Math.ceil(value.length / 4) * 4, "=");
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    return bytes.buffer;
}

export function bufferToBase64Url(buffer: ArrayBuffer | ArrayBufferView): string {
    const bytes = buffer instanceof ArrayBuffer ? new Uint8Array(buffer) : new Uint8Array(buffer.buffer, buffer.byteOffset, buffer.byteLength);
    let binary = "";
    for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
    return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}

const descriptors = (list: unknown): PublicKeyCredentialDescriptor[] =>
    Array.isArray(list) ? list.map((item: JsonObject) => ({ ...(item as object), id: base64UrlToBuffer(String(item.id)) }) as PublicKeyCredentialDescriptor) : [];

export function toCreationOptions(json: JsonObject): PublicKeyCredentialCreationOptions {
    const user = json.user as JsonObject;
    return {
        ...(json as object),
        challenge: base64UrlToBuffer(String(json.challenge)),
        user: { ...(user as object), id: base64UrlToBuffer(String(user.id)) },
        excludeCredentials: descriptors(json.excludeCredentials),
    } as PublicKeyCredentialCreationOptions;
}

export function toRequestOptions(json: JsonObject): PublicKeyCredentialRequestOptions {
    return {
        ...(json as object),
        challenge: base64UrlToBuffer(String(json.challenge)),
        allowCredentials: descriptors(json.allowCredentials),
    } as PublicKeyCredentialRequestOptions;
}

export function credentialToJson(credential: PublicKeyCredential): string {
    const response = credential.response as AuthenticatorAttestationResponse & AuthenticatorAssertionResponse;
    const encoded: JsonObject = { clientDataJSON: bufferToBase64Url(response.clientDataJSON) };
    if ("attestationObject" in response && response.attestationObject) {
        encoded.attestationObject = bufferToBase64Url(response.attestationObject);
        if (typeof response.getTransports === "function") encoded.transports = response.getTransports();
    }
    if ("authenticatorData" in response && response.authenticatorData) {
        encoded.authenticatorData = bufferToBase64Url(response.authenticatorData);
        encoded.signature = bufferToBase64Url(response.signature);
        if (response.userHandle) encoded.userHandle = bufferToBase64Url(response.userHandle);
    }
    return JSON.stringify({ id: credential.id, rawId: bufferToBase64Url(credential.rawId), type: credential.type, response: encoded });
}

export type JsonResult = { ok: boolean; status: number; data: JsonObject };

/** Same-origin JSON POST with the Laravel CSRF token (the admin fetch fence adds the deployment header). */
export async function postJson(url: string, body: JsonObject = {}): Promise<JsonResult> {
    const response = await fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "",
        },
        body: JSON.stringify(body),
    });
    const data = (await response.json().catch(() => ({}))) as JsonObject;
    return { ok: response.ok, status: response.status, data };
}

/** First validation message from a Laravel 422 payload, or a fallback. */
export function firstError(data: JsonObject, fallback: string): string {
    const errors = data.errors as Record<string, string[]> | undefined;
    const first = errors ? Object.values(errors)[0]?.[0] : undefined;
    return first ?? (typeof data.message === "string" ? data.message : fallback);
}
