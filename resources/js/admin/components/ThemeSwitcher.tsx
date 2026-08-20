import { Icon } from "@admin/components/Icon";
import { useTheme, type ThemeMode } from "@admin/providers/ThemeProvider";
import { IconButton, Menu } from "@nexora/admin-ui";

const items = [
    { value: "system", label: "System appearance", description: "Follow your device preference.", leading: <Icon name="monitor" className="h-4 w-4" /> },
    { value: "light", label: "Light mode", description: "Use Nexora's light interface.", leading: <Icon name="sun" className="h-4 w-4" /> },
    { value: "dark", label: "Dark mode", description: "Use Nexora's dark interface.", leading: <Icon name="moon" className="h-4 w-4" /> },
];

export function ThemeSwitcher() {
    const { theme, setTheme, resolved } = useTheme();
    const icon = theme === "system" ? "monitor" : theme === "dark" ? "moon" : "sun";
    const label = theme === "system" ? `Appearance: System (${resolved})` : `Appearance: ${theme === "dark" ? "Dark" : "Light"}`;

    return (
        <Menu
            value={theme}
            onSelect={(value) => setTheme(value as ThemeMode)}
            items={items}
            trigger={<IconButton label={label}><Icon name={icon} className="h-5 w-5" /></IconButton>}
        />
    );
}
