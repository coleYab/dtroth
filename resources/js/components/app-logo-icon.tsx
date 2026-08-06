import type { ImgHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return (
        <img
            {...props}
            src="/logo.png"
            alt=""
            aria-hidden
            className={cn('size-full object-contain', props.className)}
        />
    );
}
