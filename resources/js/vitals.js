import { onINP, onLCP, onCLS, onTTFB } from 'web-vitals/attribution';
import { analyticsAllowed } from './cookieConsent';

// Beacons are the only measurement this app sends, so they ride on the
// analytics consent category. Consent is read at send time rather than at
// startup, so granting or withdrawing it takes hold without a reload.
export function initVitals(router) {
    let currentRoute = 'initial';

    router.on('navigate', (event) => {
        currentRoute = event.detail.page.component;
    });

    const send = (payload) => {
        if (!analyticsAllowed()) return;

        navigator.sendBeacon('/api/vitals', JSON.stringify({
            ...payload,
            route: currentRoute,
        }));
    };

    onINP((metric) => {
        const a = metric.attribution;
        send({
            name: 'INP',
            value: Math.round(metric.value),
            target: a.interactionTarget,
            type: a.interactionType,
            inputDelay: Math.round(a.inputDelay),
            processing: Math.round(a.processingDuration),
            presentation: Math.round(a.presentationDelay),
        });
    });

    onLCP((m) => send({ name: 'LCP', value: Math.round(m.value), target: m.attribution.element }));
    onCLS((m) => send({ name: 'CLS', value: Number(m.value.toFixed(4)) }));
    onTTFB((m) => send({ name: 'TTFB', value: Math.round(m.value) }));
}
