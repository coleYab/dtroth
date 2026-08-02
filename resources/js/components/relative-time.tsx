import { useEffect, useState } from 'react';

function formatTimeAgo(date: Date): string {
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days} day${days === 1 ? '' : 's'} ago`;
    }

    return date.toLocaleDateString();
}

export function RelativeTime({ date, className }: { date: string | null; className?: string }) {
    const [, setNow] = useState(() => Date.now());

    useEffect(() => {
        const interval = setInterval(() => setNow(Date.now()), 60_000);

        return () => clearInterval(interval);
    }, []);

    if (!date) {
        return <span className={className}>—</span>;
    }

    return <span className={className}>{formatTimeAgo(new Date(date))}</span>;
}
