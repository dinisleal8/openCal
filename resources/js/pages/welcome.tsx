import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, Camera, Flame, Globe } from 'lucide-react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Log in
                            </Link>
                        )}
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col items-center text-center lg:max-w-4xl">
                        <div className="mb-8 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#1b1b18] dark:bg-[#EDEDEC]">
                            <Flame className="h-8 w-8 text-white dark:text-[#0a0a0a]" />
                        </div>
                        <h1 className="mb-3 text-3xl font-bold tracking-tight lg:text-5xl">
                            openCal
                        </h1>
                        <p className="mb-10 max-w-md text-lg text-[#706f6c] lg:text-xl dark:text-[#A1A09A]">
                            Track your calories. Reach your goals.
                        </p>

                        <div className="mb-12 grid w-full max-w-lg grid-cols-2 gap-4 text-left">
                            <div className="rounded-xl border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <Camera className="mb-2 size-5 text-[#706f6c] dark:text-[#A1A09A]" />
                                <p className="text-sm font-medium">
                                    AI Photo Recognition
                                </p>
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Snap a photo to log meals instantly
                                </p>
                            </div>
                            <div className="rounded-xl border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <Flame className="mb-2 size-5 text-[#706f6c] dark:text-[#A1A09A]" />
                                <p className="text-sm font-medium">
                                    Smart Calorie Tracking
                                </p>
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Accurate estimates powered by AI
                                </p>
                            </div>
                            <div className="rounded-xl border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <Activity className="mb-2 size-5 text-[#706f6c] dark:text-[#A1A09A]" />
                                <p className="text-sm font-medium">Band Sync</p>
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Connect your wearable for automatic data
                                </p>
                            </div>
                            <div className="rounded-xl border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <Globe className="mb-2 size-5 text-[#706f6c] dark:text-[#A1A09A]" />
                                <p className="text-sm font-medium">
                                    Multi-language
                                </p>
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Use openCal in your language
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-block rounded-sm border border-black bg-[#1b1b18] px-6 py-2.5 text-sm leading-normal font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                                >
                                    Go to Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-black bg-[#1b1b18] px-6 py-2.5 text-sm leading-normal font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                                >
                                    Get Started
                                </Link>
                            )}
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
