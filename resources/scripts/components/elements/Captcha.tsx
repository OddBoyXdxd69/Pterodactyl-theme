import React, { useEffect, useRef } from 'react';
import { useStoreState } from 'easy-peasy';

interface Props {
    onVerify: (token: string) => void;
    onExpire?: () => void;
}

export interface CaptchaRef {
    execute: () => Promise<void> | void;
    reset: () => void;
}

const Captcha = React.forwardRef<CaptchaRef, Props>(({ onVerify, onExpire }, ref) => {
    const { enabled, provider, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetIdRef = useRef<any>(null);

    useEffect(() => {
        if (!enabled || !siteKey) return;

        let scriptId = '';
        let scriptUrl = '';

        if (provider === 'turnstile') {
            scriptId = 'cloudflare-turnstile-script';
            scriptUrl = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        } else if (provider === 'hcaptcha') {
            scriptId = 'hcaptcha-script';
            scriptUrl = 'https://js.hcaptcha.com/1/api.js?render=explicit';
        } else {
            scriptId = 'recaptcha-script';
            scriptUrl = 'https://www.google.com/recaptcha/api.js?render=explicit';
        }

        const existingScript = document.getElementById(scriptId);
        if (!existingScript) {
            const script = document.createElement('script');
            script.id = scriptId;
            script.src = scriptUrl;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        const checkInterval = setInterval(() => {
            if (provider === 'turnstile' && (window as any).turnstile) {
                clearInterval(checkInterval);
                if (containerRef.current && widgetIdRef.current === null) {
                    widgetIdRef.current = (window as any).turnstile.render(containerRef.current, {
                        sitekey: siteKey,
                        callback: onVerify,
                        'expired-callback': onExpire,
                        appearance: 'execute',
                    });
                }
            } else if (provider === 'hcaptcha' && (window as any).hcaptcha) {
                clearInterval(checkInterval);
                if (containerRef.current && widgetIdRef.current === null) {
                    widgetIdRef.current = (window as any).hcaptcha.render(containerRef.current, {
                        sitekey: siteKey,
                        callback: onVerify,
                        'expired-callback': onExpire,
                        size: 'invisible',
                    });
                }
            } else if (provider === 'recaptcha' && (window as any).grecaptcha) {
                clearInterval(checkInterval);
                if (containerRef.current && widgetIdRef.current === null) {
                    widgetIdRef.current = (window as any).grecaptcha.render(containerRef.current, {
                        sitekey: siteKey,
                        callback: onVerify,
                        'expired-callback': onExpire,
                        size: 'invisible',
                    });
                }
            }
        }, 100);

        return () => {
            clearInterval(checkInterval);
            widgetIdRef.current = null;
        };
    }, [enabled, provider, siteKey]);

    React.useImperativeHandle(ref, () => ({
        execute: () => {
            if (!enabled) return;
            if (provider === 'turnstile' && (window as any).turnstile && widgetIdRef.current !== null) {
                (window as any).turnstile.execute(widgetIdRef.current);
            } else if (provider === 'hcaptcha' && (window as any).hcaptcha && widgetIdRef.current !== null) {
                (window as any).hcaptcha.execute(widgetIdRef.current);
            } else if (provider === 'recaptcha' && (window as any).grecaptcha && widgetIdRef.current !== null) {
                (window as any).grecaptcha.execute(widgetIdRef.current);
            }
        },
        reset: () => {
            if (!enabled) return;
            if (provider === 'turnstile' && (window as any).turnstile && widgetIdRef.current !== null) {
                (window as any).turnstile.reset(widgetIdRef.current);
            } else if (provider === 'hcaptcha' && (window as any).hcaptcha && widgetIdRef.current !== null) {
                (window as any).hcaptcha.reset(widgetIdRef.current);
            } else if (provider === 'recaptcha' && (window as any).grecaptcha && widgetIdRef.current !== null) {
                (window as any).grecaptcha.reset(widgetIdRef.current);
            }
        }
    }));

    if (!enabled) return null;

    return (
        <div 
            ref={containerRef} 
            style={{ width: 0, height: 0, overflow: 'hidden', position: 'absolute' }}
        />
    );
});

Captcha.displayName = 'Captcha';
export default Captcha;
