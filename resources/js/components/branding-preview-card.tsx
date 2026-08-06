import { ShieldCheck } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SectionApp } from '@/types';

type BrandingPreviewCardProps = {
    app: SectionApp;
    logoUrl: string | null;
    brandColor: string;
};

export function BrandingPreviewCard({
    app,
    logoUrl,
    brandColor,
}: BrandingPreviewCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Live preview</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="bg-muted rounded-lg p-4">
                    <div className="bg-background mx-auto max-w-[280px] overflow-hidden rounded-2xl border shadow-sm">
                        <div
                            className="flex items-center justify-center gap-2 px-4 py-5"
                            style={{ backgroundColor: brandColor }}
                        >
                            {logoUrl ? (
                                <img
                                    src={logoUrl}
                                    alt={`${app.name} logo`}
                                    className="max-h-10 max-w-24 object-contain"
                                />
                            ) : (
                                <div className="flex items-center gap-2 text-white">
                                    <ShieldCheck className="size-5" />
                                    <span className="text-sm font-medium">
                                        {app.name}
                                    </span>
                                </div>
                            )}
                        </div>
                        <div className="space-y-3 p-4">
                            <p className="text-sm font-medium">
                                Verify your age
                            </p>
                            <p className="text-muted-foreground text-xs leading-relaxed">
                                Your age will be shared as a single value (e.g.
                                “18+”) and never your date of birth.
                            </p>
                            <div className="flex gap-2 pt-1">
                                <span
                                    className="flex-1 rounded-lg px-3 py-2 text-center text-xs font-medium text-white"
                                    style={{ backgroundColor: brandColor }}
                                >
                                    Continue
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
