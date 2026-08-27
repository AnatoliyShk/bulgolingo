import * as CookieConsent from 'vanilla-cookieconsent'
import 'vanilla-cookieconsent/dist/cookieconsent.css'
import './assets/scss/components/cookie-consent.scss'

export const ANALYTICS_CATEGORY = 'analytics'

// Mirrors the plugin's own state so callers can ask about consent before the
// banner has booted, where the plugin itself has nothing to answer with.
let analyticsGranted = false

// True once the visitor has allowed the analytics category. The plugin's own
// callbacks keep this current, so callers that ask on every use see a
// withdrawal take effect straight away rather than at the next page load.
export function analyticsAllowed() {
    return analyticsGranted
}

// Opens the preferences dialog. Any element carrying
// data-cc="show-preferencesModal" opens it too, without going through here.
export function showCookiePreferences() {
    CookieConsent.showPreferences()
}

/**
 * Boots the consent banner. Everything but the strictly necessary category is
 * off until the visitor opts in, and the analytics category gates the web
 * vitals beacons, which are the only measurement this app sends.
 */
export function initCookieConsent() {
    const syncAnalytics = () => {
        analyticsGranted = CookieConsent.acceptedCategory(ANALYTICS_CATEGORY)
    }

    return CookieConsent.run({
        guiOptions: {
            consentModal: { layout: 'box', position: 'bottom left', equalWeightButtons: true },
            preferencesModal: { layout: 'box', equalWeightButtons: true },
        },

        categories: {
            necessary: { enabled: true, readOnly: true },
            analytics: {},
        },

        onConsent: syncAnalytics,
        onChange: syncAnalytics,

        language: {
            default: 'en',
            translations: {
                en: {
                    consentModal: {
                        title: 'Cookies on BalkanBuddy',
                        description:
                            'We use cookies to keep you signed in and to remember your settings. We would also like to measure how quickly pages load, but only if you allow it.',
                        acceptAllBtn: 'Accept all',
                        acceptNecessaryBtn: 'Reject optional',
                        showPreferencesBtn: 'Choose what to allow',
                    },
                    preferencesModal: {
                        title: 'Cookie preferences',
                        acceptAllBtn: 'Accept all',
                        acceptNecessaryBtn: 'Reject optional',
                        savePreferencesBtn: 'Save my choices',
                        closeIconLabel: 'Close',
                        sections: [
                            {
                                title: 'How we use cookies',
                                description:
                                    'Some cookies are needed for the site to work at all. Everything else is your choice, and you can change it whenever you like.',
                            },
                            {
                                title: 'Strictly necessary',
                                description:
                                    'Keeps you signed in, protects forms against cross-site request forgery, and stores this very choice. The site cannot work without these.',
                                linkedCategory: 'necessary',
                                cookieTable: {
                                    caption: 'Necessary cookies',
                                    headers: { name: 'Cookie', description: 'What it does', duration: 'Kept for' },
                                    body: [
                                        {
                                            name: 'balkanbuddy-session',
                                            description: 'Identifies your browsing session so you stay signed in.',
                                            duration: '2 hours',
                                        },
                                        {
                                            name: 'XSRF-TOKEN',
                                            description: 'Proves a form was submitted from this site and not another one.',
                                            duration: '2 hours',
                                        },
                                        {
                                            name: 'cc_cookie',
                                            description: 'Remembers the choice you make in this dialog.',
                                            duration: '6 months',
                                        },
                                    ],
                                },
                            },
                            {
                                title: 'Performance measurement',
                                description:
                                    'Lets us record how long pages take to load and respond, so slow screens can be found and fixed. The measurements are not tied to your identity and no cookie is set for them.',
                                linkedCategory: 'analytics',
                            },
                        ],
                    },
                },
            },
        },
    })
}
