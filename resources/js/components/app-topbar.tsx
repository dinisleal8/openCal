import { Link, usePage } from '@inertiajs/react';
import { Flame, Globe } from 'lucide-react';
import { UserMenuContent } from '@/components/user-menu-content';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { useI18n } from '@/lib/i18n';
import { dashboard } from '@/routes';

export function AppTopbar() {
    const { auth } = usePage().props;
    const { t, locale, setLocale } = useI18n();
    const getInitials = useInitials();

    return (
        <header className="bg-background/85 fixed inset-x-0 top-0 z-40 border-b backdrop-blur-md">
            <div className="mx-auto flex h-14 w-full max-w-xl items-center justify-between px-4">
                <Link
                    href={dashboard()}
                    prefetch
                    className="flex items-center gap-2"
                >
                    <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-xl">
                        <Flame className="size-4.5" />
                    </span>
                    <span className="text-base font-semibold tracking-tight">
                        {t('appName')}
                    </span>
                </Link>

                <div className="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-muted-foreground gap-1.5 px-2"
                        onClick={() => setLocale(locale === 'pt' ? 'en' : 'pt')}
                        aria-label={t('common.language')}
                    >
                        <Globe className="size-4" />
                        <span className="text-xs font-medium uppercase">
                            {locale}
                        </span>
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                className="focus-visible:ring-ring ml-1 flex size-9 items-center justify-center rounded-full outline-none focus-visible:ring-2"
                                aria-label={t('nav.openMenu')}
                            >
                                <Avatar className="size-9 overflow-hidden rounded-full">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="rounded-full bg-neutral-200 font-medium text-black dark:bg-neutral-700 dark:text-white">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>
    );
}
