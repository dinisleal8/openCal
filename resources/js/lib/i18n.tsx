import { router, usePage } from '@inertiajs/react';
import {
    createContext,
    useContext,
    useMemo,
    type PropsWithChildren,
} from 'react';
import en from '@/lang/en';
import pt from '@/lang/pt';
import { update as localeRoute } from '@/routes/locale';

const dictionaries = { en, pt } as const;

export type Locale = keyof typeof dictionaries;

type Dictionary = typeof en;

type NestedKeyOf<T> = T extends string
    ? never
    : {
          [K in keyof T & string]: T[K] extends object
              ? `${K}.${NestedKeyOf<T[K]>}`
              : K;
      }[keyof T & string];

export type TranslationKey = NestedKeyOf<Dictionary>;

export type TranslateFn = (
    key: TranslationKey,
    replace?: Record<string, string | number>,
) => string;

type I18nContextValue = {
    locale: Locale;
    t: TranslateFn;
    setLocale: (locale: Locale) => void;
};

const I18nContext = createContext<I18nContextValue | null>(null);

function lookup(dict: object, key: string): string | undefined {
    const value = key
        .split('.')
        .reduce<unknown>(
            (acc, part) =>
                acc !== null && typeof acc === 'object'
                    ? (acc as Record<string, unknown>)[part]
                    : undefined,
            dict,
        );

    return typeof value === 'string' ? value : undefined;
}

export function I18nProvider({ children }: PropsWithChildren) {
    const { locale } = usePage().props;
    const active: Locale =
        typeof locale === 'string' && locale in dictionaries
            ? (locale as Locale)
            : 'en';

    const value = useMemo<I18nContextValue>(
        () => ({
            locale: active,
            t: (key, replace) => {
                const template =
                    lookup(dictionaries[active], key) ??
                    lookup(dictionaries.en, key) ??
                    key;

                if (!replace) {
                    return template;
                }

                return template.replace(/\{(\w+)\}/g, (_, token: string) =>
                    token in replace ? String(replace[token]) : `{${token}}`,
                );
            },
            setLocale: (next) => {
                router.put(localeRoute(next), undefined, {
                    preserveScroll: true,
                    preserveState: true,
                });
            },
        }),
        [active],
    );

    return (
        <I18nContext.Provider value={value}>{children}</I18nContext.Provider>
    );
}

export function useI18n(): I18nContextValue {
    const context = useContext(I18nContext);

    if (!context) {
        throw new Error('useI18n must be used within an I18nProvider');
    }

    return context;
}
