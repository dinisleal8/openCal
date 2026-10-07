import { useCallback, useEffect, useRef, useState } from 'react';

type Recognition = {
    lang: string;
    interimResults: boolean;
    continuous: boolean;
    maxAlternatives: number;
    start: () => void;
    stop: () => void;
    abort: () => void;
    onresult: ((event: unknown) => void) | null;
    onend: (() => void) | null;
    onerror: ((event: unknown) => void) | null;
};

export function useSpeechRecognition(
    lang: string,
    onResult: (text: string) => void,
) {
    const [supported] = useState<boolean>(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        const w = window as typeof window & {
            SpeechRecognition?: new () => Recognition;
            webkitSpeechRecognition?: new () => Recognition;
        };

        return Boolean(w.SpeechRecognition || w.webkitSpeechRecognition);
    });

    const [listening, setListening] = useState(false);
    const recognitionRef = useRef<Recognition | null>(null);
    const onResultRef = useRef(onResult);
    onResultRef.current = onResult;

    const stop = useCallback(() => {
        recognitionRef.current?.stop();
    }, []);

    const start = useCallback(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const w = window as typeof window & {
            SpeechRecognition?: new () => Recognition;
            webkitSpeechRecognition?: new () => Recognition;
        };

        const Ctor = w.SpeechRecognition || w.webkitSpeechRecognition;

        if (!Ctor) {
            return;
        }

        recognitionRef.current?.stop();

        const recognition: Recognition = new Ctor();
        recognition.lang = lang;
        recognition.interimResults = false;
        recognition.continuous = false;
        recognition.maxAlternatives = 1;
        recognition.onresult = (event) => {
            const result = (
                event as {
                    results?: ArrayLike<ArrayLike<{ transcript: string }>>;
                    resultIndex: number;
                }
            ).results?.[(event as { resultIndex: number }).resultIndex];
            const text = result?.[0]?.transcript ?? '';

            if (text) {
                onResultRef.current(text);
            }
        };
        recognition.onend = () => {
            setListening(false);
            recognitionRef.current = null;
        };
        recognition.onerror = () => {
            setListening(false);
            recognitionRef.current = null;
        };

        recognitionRef.current = recognition;
        setListening(true);
        recognition.start();
    }, [lang]);

    useEffect(() => {
        return () => {
            recognitionRef.current?.abort();
        };
    }, []);

    return { supported, listening, start, stop };
}
