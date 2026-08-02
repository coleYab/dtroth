import { ShieldCheck } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type ConsentPreviewCardProps = {
    consentText: string;
    appName: string;
    brandColor?: string;
};

export function ConsentPreviewCard({ consentText, appName, brandColor = '#0f172a' }: ConsentPreviewCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Live preview</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="rounded-lg bg-muted p-4">
                    <div className="mx-auto max-w-[280px] overflow-hidden rounded-2xl border bg-background shadow-sm">
                        <div
                            className="flex items-center gap-2 px-4 py-3"
                            style={{ backgroundColor: brandColor }}
                        >
                            <ShieldCheck className="size-4 text-white" />
                            <p className="text-sm font-medium text-white">{appName}</p>
                        </div>
                        <div className="space-y-3 p-4">
                            <p className="text-sm font-medium">Verify your age</p>
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                {consentText || 'No consent text set yet.'}
                            </p>
                            <div className="flex gap-2 pt-1">
                                <span className="flex-1 rounded-lg bg-primary px-3 py-2 text-center text-xs font-medium text-primary-foreground">
                                    Continue
                                </span>
                            </div>
                            <p className="text-center text-[10px] text-muted-foreground">
                                Powered by Troth · age shared as a single value
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
