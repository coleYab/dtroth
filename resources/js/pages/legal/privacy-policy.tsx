import { Head } from '@inertiajs/react';
import LandingHeader from '@/components/landing-header';

export default function PrivacyPolicy() {
    return (
        <>
            <Head title="Privacy Policy" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a]">
                <LandingHeader />
                <main className="w-full max-w-[335px] rounded-lg bg-white p-6 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:max-w-4xl lg:p-12 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h1 className="mb-1 text-2xl font-medium">Privacy Policy</h1>
                    <p className="mb-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Last updated: August 1, 2026
                    </p>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">1. Information We Collect</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We collect information you provide directly to us,
                            such as your name, email address, and password when
                            you create an account. We also collect information
                            about how you use our service.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">2. How We Use Your Information</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We use the information we collect to provide,
                            maintain, and improve our services, to communicate
                            with you, and to protect the security and integrity
                            of our platform.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">3. Sharing of Information</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We do not sell your personal information. We may
                            share your information with service providers who
                            assist us in operating our service, or when required
                            by law.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">4. Data Security</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We take reasonable measures to protect your personal
                            information from unauthorized access, use, or
                            disclosure. However, no method of transmission over
                            the internet is completely secure.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">5. Your Rights</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            You have the right to access, update, or delete your
                            personal information at any time through your
                            account settings.
                        </p>
                    </section>

                    <section>
                        <h2 className="mb-2 font-medium">6. Changes to This Policy</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We may update this Privacy Policy from time to time.
                            We will notify you of any changes by posting the new
                            policy on this page.
                        </p>
                    </section>
                </main>
            </div>
        </>
    );
}
