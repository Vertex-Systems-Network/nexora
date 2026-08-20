import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import type { AppearanceSettings } from "@admin/types/page";

export type ThemeMode = AppearanceSettings["theme"];

type ThemeContextValue = {
    theme: ThemeMode;
    setTheme: (theme: ThemeMode) => void;
    resolved: "light" | "dark";
};

const ThemeContext = createContext<ThemeContextValue | null>(null);
const STORAGE_KEY = "nexora.admin.theme";

function hexToRgb(hex: string): string {
    const clean = hex.replace("#", "");
    const r = Number.parseInt(clean.slice(0, 2), 16);
    const g = Number.parseInt(clean.slice(2, 4), 16);
    const b = Number.parseInt(clean.slice(4, 6), 16);
    return `${r} ${g} ${b}`;
}

const fallbackAppearance: AppearanceSettings = {
    theme: "system",
    primary: "#7f56d9",
    density: "comfortable",
    radius: "medium",
};

function storedTheme(): ThemeMode | null {
    if (typeof window === "undefined") return null;
    const value = window.localStorage.getItem(STORAGE_KEY);
    return value === "light" || value === "dark" || value === "system" ? value : null;
}

export function ThemeProvider({ children, appearance = fallbackAppearance }: { children: ReactNode; appearance?: AppearanceSettings }) {
    const [theme, setThemeState] = useState<ThemeMode>(() => storedTheme() ?? appearance.theme);
    const [resolved, setResolved] = useState<"light" | "dark">("light");

    useEffect(() => {
        if (storedTheme() === null) setThemeState(appearance.theme);
    }, [appearance.theme]);

    const setTheme = useCallback((next: ThemeMode) => {
        setThemeState(next);
        window.localStorage.setItem(STORAGE_KEY, next);
    }, []);

    useEffect(() => {
        const root = document.documentElement;
        const media = window.matchMedia("(prefers-color-scheme: dark)");
        const apply = () => {
            const dark = theme === "dark" || (theme === "system" && media.matches);
            root.classList.toggle("dark", dark);
            root.dataset.density = appearance.density;
            root.dataset.radius = appearance.radius;
            root.style.setProperty("--nx-brand-600", appearance.primary);
            root.style.setProperty("--nx-brand-rgb", hexToRgb(appearance.primary));
            root.style.colorScheme = dark ? "dark" : "light";
            setResolved(dark ? "dark" : "light");
        };
        apply();
        media.addEventListener("change", apply);
        return () => media.removeEventListener("change", apply);
    }, [appearance.density, appearance.primary, appearance.radius, theme]);

    const value = useMemo(() => ({ theme, setTheme, resolved }), [theme, setTheme, resolved]);
    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
    const context = useContext(ThemeContext);
    if (!context) throw new Error("useTheme must be used inside ThemeProvider.");
    return context;
}
