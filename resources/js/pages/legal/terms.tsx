import { Head } from '@inertiajs/react';
import LandingHeader from '@/components/landing-header';

export default function Terms() {
    return (
        <>
            <Head title="Terms of Service" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a]">
                <LandingHeader />
                <main className="w-full max-w-[335px] rounded-lg bg-white p-6 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:max-w-4xl lg:p-12 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h1 className="mb-1 text-2xl font-medium">Terms of Service</h1>
                    <p className="mb-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Last updated: August 1, 2026
                    </p>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">1. Acceptance of Terms</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            By accessing or using our services, you agree to be
                            bound by these Terms of Service. If you do not agree
                            to any part of these terms, you may not access or
                            use our services.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">2. Use of the Service</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            You agree to use the service only for lawful purposes
                            and in accordance with these terms. You are
                            responsible for maintaining the confidentiality of
                            your account credentials and for all activities that
                            occur under your account.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">3. Account Responsibilities</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            You must provide accurate and complete information
                            when creating an account. You must notify us
                            immediately of any unauthorized use of your account
                            or any other breach of security.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">4. Intellectual Property</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            All content, features, and functionality of the
                            service, including text, graphics, logos, and
                            software, are the exclusive property of our company
                            and are protected by intellectual property laws.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">5. Termination</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We may terminate or suspend your access to the
                            service immediately, without prior notice or
                            liability, for any reason, including if you breach
                            these Terms of Service.
                        </p>
                    </section>

                    <section className="mb-6">
                        <h2 className="mb-2 font-medium">6. Limitation of Liability</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            In no event shall we be liable for any indirect,
                            incidental, special, consequential, or punitive
                            damages arising out of your use of the service.
                        </p>
                    </section>

                    <section>
                        <h2 className="mb-2 font-medium">7. Changes to These Terms</h2>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We reserve the right to modify these terms at any
                            time. Your continued use of the service after any
                            changes constitutes acceptance of the new terms.
                        </p>
                    </section>
                </main>
            </div>
        </>
    );
}
