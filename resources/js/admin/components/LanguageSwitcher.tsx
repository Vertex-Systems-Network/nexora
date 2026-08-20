import { router } from "@inertiajs/react";
import { Select } from "@nexora/admin-ui";
import type { LocalizationProps } from "@admin/types/page";

export function LanguageSwitcher({ localization }: { localization: LocalizationProps }) {
    return (
        <div className="w-44">
            <Select
                ariaLabel={localization.messages.language}
                value={localization.locale}
                onChange={(locale) => {
                    router.post("/locale", { locale }, {
                        preserveScroll: true,
                        preserveState: false,
                        onSuccess: () => window.location.reload(),
                    });
                }}
                options={localization.supported.map((locale) => ({
                    value: locale.code,
                    label: locale.name,
                    description: locale.native !== locale.name ? locale.native : undefined,
                    leading: locale.flagUrl ? <img src={locale.flagUrl} alt="" className="h-4 w-6 rounded-[3px] object-cover ring-1 ring-black/5" /> : <span className="text-base leading-none" aria-hidden="true">{locale.flag}</span>,
                }))}
            />
        </div>
    );
}
