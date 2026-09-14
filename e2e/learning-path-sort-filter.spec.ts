import { test, expect, Page, Route } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

type Path = { id: number; name: string; language: string; type: string; exercise_types: string[]; exercise_count: number };
type Filters = { level: string | null; sort: string };
type Props = Record<string, unknown>;
type InertiaPage = { component: string; props: Props; url: string; version: string | null };

const PATHS: Path[] = [
    { id: 9201, name: 'Fewest exercises', language: 'bg', type: 'regular', exercise_types: [], exercise_count: 1 },
    { id: 9202, name: 'Most exercises', language: 'bg', type: 'regular', exercise_types: [], exercise_count: 9 },
];

const DEFAULT_FILTERS: Filters = { level: null, sort: 'exercises_desc' };

const FIRST_LOAD: Props = {
    paths: [...PATHS].reverse(),
    unfinishedPaths: [],
    finishedPaths: [],
    search: { enabled: true, query: '', unavailable: false },
    levels: [{ value: 'A1', label: 'A1 Beginner' }],
    filters: DEFAULT_FILTERS,
};

// Rewrites the page object the server rendered on first load, once the
// document is parsed and before the deferred app script mounts, so the test
// starts from exactly the catalog it describes. The HTML response itself is
// left alone: fulfilling it would make Chrome treat the page as foreign and
// refuse its scripts from the Vite dev server.
async function openCatalog(page: Page, overrides: Props = {}): Promise<InertiaPage> {
    await page.addInitScript((props: Props) => {
        document.addEventListener('readystatechange', () => {
            if (document.readyState !== 'interactive') {
                return;
            }

            const app = document.getElementById('app');

            if (!app?.dataset.page) {
                return;
            }

            const data = JSON.parse(app.dataset.page);
            data.props = { ...data.props, ...props };
            app.dataset.page = JSON.stringify(data);
        });
    }, { ...FIRST_LOAD, ...overrides });

    await page.goto(`${BASE}/learning-paths`);
    await expect(page.getByRole('heading', { name: 'All learning paths' })).toBeVisible();

    return page.evaluate(() => JSON.parse(document.getElementById('app')?.dataset.page ?? '{}'));
}

// Answers every Inertia visit to the catalog made after the first load: the
// filters echo the address, and the catalog is reordered by the requested
// sort. `delayMs` holds the reply back to leave the in-flight state
// observable. Full page loads pass through untouched.
async function answerVisits(page: Page, base: InertiaPage, delayMs = 0): Promise<string[]> {
    const seen: string[] = [];

    await page.route(/\/learning-paths(\?.*)?$/, async (route: Route) => {
        const request = route.request();

        if (!request.headers()['x-inertia']) {
            return route.continue();
        }

        const url = new URL(request.url());
        seen.push(url.search);

        const sort = url.searchParams.get('sort') === 'exercises_asc' ? 'exercises_asc' : 'exercises_desc';
        const ordered = [...PATHS].sort((a, b) =>
            sort === 'exercises_asc' ? a.exercise_count - b.exercise_count : b.exercise_count - a.exercise_count);

        if (delayMs) {
            await new Promise((resolve) => setTimeout(resolve, delayMs));
        }

        await route.fulfill({
            status: 200,
            headers: { 'Content-Type': 'application/json', 'X-Inertia': 'true', Vary: 'X-Inertia' },
            body: JSON.stringify({
                ...base,
                url: url.pathname + url.search,
                props: {
                    ...base.props,
                    errors: {},
                    paths: ordered,
                    search: { enabled: true, query: url.searchParams.get('q') ?? '', unavailable: false },
                    filters: { level: url.searchParams.get('level'), sort },
                },
            }),
        });
    });

    return seen;
}

const filter = (page: Page) => page.getByRole('group', { name: 'Sort learning paths by exercise count' });
const mostButton = (page: Page) => filter(page).getByRole('button', { name: 'Most exercises' });
const fewestButton = (page: Page) => filter(page).getByRole('button', { name: 'Fewest exercises' });
const cardNames = (page: Page) => page.locator('.nb-paths__grid-item .nb-path-card-mini__name');

test.describe('Learning path sort filter', () => {
    test('defaults to most exercises first with no way to turn sorting off', async ({ page }) => {
        await openCatalog(page);

        await expect(filter(page)).toBeVisible();
        await expect(mostButton(page)).toHaveAttribute('aria-pressed', 'true');
        await expect(fewestButton(page)).toHaveAttribute('aria-pressed', 'false');
        await expect(filter(page).getByRole('button', { name: 'Default' })).toHaveCount(0);
        await expect(cardNames(page)).toHaveText(['Most exercises', 'Fewest exercises']);
    });

    test('switches to fewest exercises first', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerVisits(page, base);

        await fewestButton(page).click();

        await expect(page).toHaveURL(`${BASE}/learning-paths?sort=exercises_asc`);
        expect(seen).toEqual(['?sort=exercises_asc']);
        await expect(fewestButton(page)).toHaveAttribute('aria-pressed', 'true');
        await expect(mostButton(page)).toHaveAttribute('aria-pressed', 'false');
        await expect(cardNames(page)).toHaveText(['Fewest exercises', 'Most exercises']);
    });

    test('does nothing when the already-active sort is clicked again', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerVisits(page, base);

        await mostButton(page).click();

        expect(seen).toEqual([]);
        await expect(page).toHaveURL(`${BASE}/learning-paths`);
    });

    test('disables both buttons while the reordered list loads', async ({ page }) => {
        const base = await openCatalog(page);
        await answerVisits(page, base, 800);

        await fewestButton(page).click();

        await expect(fewestButton(page)).toBeDisabled();
        await expect(mostButton(page)).toBeDisabled();

        await expect(cardNames(page)).toHaveText(['Fewest exercises', 'Most exercises']);
        await expect(fewestButton(page)).toBeEnabled();
        await expect(mostButton(page)).toBeEnabled();
    });

    test('keeps the search and the level already narrowing the page', async ({ page }) => {
        const base = await openCatalog(page, {
            search: { enabled: true, query: 'town', unavailable: false },
            filters: { ...DEFAULT_FILTERS, level: 'A1' },
        });
        const seen = await answerVisits(page, base);

        await fewestButton(page).click();

        await expect.poll(() => seen).toEqual(['?q=town&level=A1&sort=exercises_asc']);
        await expect(page.getByRole('searchbox', { name: 'Search learning paths by topic' })).toHaveValue('town');
        await expect(page.getByRole('button', { name: 'A1 Beginner' })).toHaveAttribute('aria-pressed', 'true');
    });

    test('going back restores the earlier sort', async ({ page }) => {
        const base = await openCatalog(page);
        await answerVisits(page, base);

        await fewestButton(page).click();
        await expect(page).toHaveURL(`${BASE}/learning-paths?sort=exercises_asc`);

        await page.goBack();

        await expect(page).toHaveURL(`${BASE}/learning-paths`);
        await expect(mostButton(page)).toHaveAttribute('aria-pressed', 'true');
        await expect(cardNames(page)).toHaveText(['Most exercises', 'Fewest exercises']);
    });

    test('follows the dark theme', async ({ page }) => {
        await page.addInitScript(() => localStorage.setItem('theme', 'dark'));
        await openCatalog(page);

        await expect(filter(page)).toHaveClass(/\bdark\b/);
    });
});
