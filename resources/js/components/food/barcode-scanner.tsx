import {
    BrowserMultiFormatReader,
    type IScannerControls,
} from '@zxing/browser';
import { useEffect, useRef } from 'react';

type Props = {
    onDetected: (code: string) => void;
    onError: () => void;
};

export function BarcodeScanner({ onDetected, onError }: Props) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const controlsRef = useRef<IScannerControls | null>(null);
    const onDetectedRef = useRef(onDetected);
    const onErrorRef = useRef(onError);

    onDetectedRef.current = onDetected;
    onErrorRef.current = onError;

    useEffect(() => {
        const video = videoRef.current;

        if (!video) {
            return;
        }

        let disposed = false;
        const reader = new BrowserMultiFormatReader();

        reader
            .decodeFromVideoDevice(undefined, video, (result) => {
                if (result && !disposed) {
                    disposed = true;
                    controlsRef.current?.stop();
                    onDetectedRef.current(result.getText());
                }
            })
            .then((controls) => {
                if (disposed) {
                    controls.stop();
                } else {
                    controlsRef.current = controls;
                }
            })
            .catch(() => {
                if (!disposed) {
                    disposed = true;
                    onErrorRef.current();
                }
            });

        return () => {
            disposed = true;
            controlsRef.current?.stop();
        };
    }, []);

    return (
        <video
            ref={videoRef}
            className="aspect-square w-full rounded-xl border bg-black object-cover"
            muted
            playsInline
        />
    );
}
