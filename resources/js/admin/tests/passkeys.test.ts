import { describe, expect, it } from "vitest";
import { base64UrlToBuffer, bufferToBase64Url, firstError, toCreationOptions, toRequestOptions } from "@admin/utils/passkeys";

const bytes = (buffer: ArrayBuffer | BufferSource | undefined) => Array.from(new Uint8Array(buffer as ArrayBuffer));

describe("passkey helpers", () => {
    it("round-trips base64url without padding, including url-unsafe bytes", () => {
        const input = new Uint8Array([0, 250, 251, 252, 253, 254, 255, 62, 63]);
        const encoded = bufferToBase64Url(input);

        expect(encoded).not.toMatch(/[+/=]/);
        expect(bytes(base64UrlToBuffer(encoded))).toEqual(Array.from(input));
    });

    it("decodes binary fields of creation options and keeps the rest", () => {
        const options = toCreationOptions({
            rp: { name: "Nexora", id: "localhost" },
            user: { id: bufferToBase64Url(new Uint8Array([1, 2, 3])), name: "a@example.test", displayName: "A" },
            challenge: bufferToBase64Url(new Uint8Array([9, 8, 7])),
            excludeCredentials: [{ type: "public-key", id: bufferToBase64Url(new Uint8Array([4, 5])) }],
            pubKeyCredParams: [{ type: "public-key", alg: -7 }],
        });

        expect(bytes(options.challenge)).toEqual([9, 8, 7]);
        expect(bytes(options.user.id)).toEqual([1, 2, 3]);
        expect(bytes(options.excludeCredentials?.[0].id)).toEqual([4, 5]);
        expect(options.rp.id).toBe("localhost");
        expect(options.pubKeyCredParams[0].alg).toBe(-7);
    });

    it("decodes request options and defaults allowCredentials to an empty list", () => {
        const options = toRequestOptions({ challenge: bufferToBase64Url(new Uint8Array([1])), rpId: "localhost", userVerification: "required" });

        expect(bytes(options.challenge)).toEqual([1]);
        expect(options.allowCredentials).toEqual([]);
        expect(options.userVerification).toBe("required");
    });

    it("surfaces the first Laravel validation message", () => {
        expect(firstError({ errors: { passkey: ["Nope"] } }, "fallback")).toBe("Nope");
        expect(firstError({ message: "Too many" }, "fallback")).toBe("Too many");
        expect(firstError({}, "fallback")).toBe("fallback");
    });
});
