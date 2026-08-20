import { useEffect, useRef, useState } from "react";
import { router } from "@inertiajs/react";

export function RouteProgress() {
    const [visible, setVisible] = useState(false);
    const timer = useRef<number | null>(null);

    useEffect(() => {
        const removeStart = router.on("start", () => {
            timer.current = window.setTimeout(() => setVisible(true), 120);
        });
        const removeFinish = router.on("finish", () => {
            if (timer.current !== null) window.clearTimeout(timer.current);
            timer.current = null;
            window.setTimeout(() => setVisible(false), 90);
        });

        return () => {
            removeStart();
            removeFinish();
            if (timer.current !== null) window.clearTimeout(timer.current);
        };
    }, []);

    return visible ? <div className="nx-route-progress" role="progressbar" aria-label="Loading page" /> : null;
}
