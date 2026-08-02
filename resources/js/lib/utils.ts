import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function shortAppName(name: string): string {
    return name.length > 8 ? `${name.slice(0, 8)}…` : name;
}

export function shortId(id: string): string {
    return id.length > 10 ? `${id.slice(0, 10)}…` : id;
}
