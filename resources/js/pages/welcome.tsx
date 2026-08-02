import { Head } from '@inertiajs/react';
import LandingCta from '@/components/landing/cta';
import LandingFeatures from '@/components/landing/features';
import LandingFooter from '@/components/landing/footer';
import LandingHero from '@/components/landing/hero';
import LandingHowItWorks from '@/components/landing/how-it-works';
import LandingMarquee from '@/components/landing/marquee';
import LandingStats from '@/components/landing/stats';
import LandingTerminal from '@/components/landing/terminal-demo';
import LandingHeader from '@/components/landing-header';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="bg-background relative flex min-h-screen flex-col overflow-x-hidden">
                <LandingHeader />
                <main className="flex flex-1 flex-col">
                    <LandingHero />
                    <LandingMarquee />
                    <LandingFeatures />
                    <LandingTerminal />
                    <LandingHowItWorks />
                    <LandingStats />
                    <LandingCta />
                </main>
                <LandingFooter />
            </div>
        </>
    );
}
