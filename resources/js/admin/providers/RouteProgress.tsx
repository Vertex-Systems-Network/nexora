import { useEffect, useRef, useState } from "react";
import { router } from "@inertiajs/react";

export function RouteProgress() {
    const [visible, setVisible] = useState(false);
    const showTimer = useRef<number | null>(null);
    const hideTimer = useRef<number | null>(null);

    useEffect(() => {
        const clearTimers = () => {
            if (showTimer.current !== null) window.clearTimeout(showTimer.current);
            if (hideTimer.current !== null) window.clearTimeout(hideTimer.current);
            showTimer.current = null;
            hideTimer.current = null;
        };

        const removeStart = router.on("start", () => {
            clearTimers();
            showTimer.current = window.setTimeout(() => {
                showTimer.current = null;
                setVisible(true);
            }, 120);
        });
        const removeFinish = router.on("finish", () => {
            if (showTimer.current !== null) window.clearTimeout(showTimer.current);
            showTimer.current = null;
            if (hideTimer.current !== null) window.clearTimeout(hideTimer.current);
            hideTimer.current = window.setTimeout(() => {
                hideTimer.current = null;
                setVisible(false);
            }, 90);
        });

        return () => {
            removeStart();
            removeFinish();
            clearTimers();
        };
    }, []);

    return visible ? (
        <div
            className="nx-route-progress"
            role="progressbar"
            aria-label="Loading page"
            aria-valuetext="Loading"
        />
    ) : null;
}
