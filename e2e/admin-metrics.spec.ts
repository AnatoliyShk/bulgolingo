import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, created for admin-metrics-page verification.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// Seeded non-admin credentials (see database/seeders; used for the Stats page e2e specs too).
const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page, email: string, password: string): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

const loginAsAdmin = (page: Page) => login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
const loginAsUser = (page: Page) => login(page, USER_EMAIL, USER_PASSWORD);

// Requests are split into an "admin panel" page and a "everything else" page
// (see App\Http\Middleware\RequestMetrics::area()), so the two pages share
// the exact same UI/behavior contract — only the traffic feeding them
// differs. Run the same suite against both URLs instead of duplicating it.
function describeRequestMetricsPage(path: string, breadcrumbLabel: string, navLinkName: RegExp) {
    test.describe(`Admin request metrics page (${breadcrumbLabel})`, () => {
        test('redirects guests to the login page', async ({ page }) => {
            await page.goto(`${BASE}${path}`);
            await expect(page).toHaveURL(`${BASE}/login`);
        });

        test.describe('when authenticated as a non-admin', () => {
            test('is forbidden', async ({ page }) => {
                test.skip(!(await loginAsUser(page)), 'seeded non-admin test user is unavailable in this environment');

                const response = await page.goto(`${BASE}${path}`);
                expect(response?.status()).toBe(403);
            });
        });

        test.describe('when authenticated as an admin', () => {
            test.beforeEach(async ({ page }) => {
                test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');
                await page.goto(`${BASE}${path}`);
            });

            test('renders the KPI cards with numeric values', async ({ page }) => {
                const kpis = page.locator('.admin-metrics__kpi');
                await expect(kpis).toHaveCount(3);

                await expect(kpis.nth(0)).toContainText('Total requests recorded');
                await expect(kpis.nth(0).locator('.admin-metrics__kpi-value')).toHaveText(/^\d+$/);

                await expect(kpis.nth(1)).toContainText('P95 request duration');
                await expect(kpis.nth(1).locator('.admin-metrics__kpi-value')).toHaveText(/^[\d.]+ms$/);

                await expect(kpis.nth(2)).toContainText('Cache hit rate');
                await expect(kpis.nth(2).locator('.admin-metrics__kpi-value')).toHaveText(/^([\d.]+%|—)$/);
                await expect(kpis.nth(2).locator('.admin-metrics__kpi-sub')).toHaveText(/^\d+ hits · \d+ misses$/);
            });

            test('shows the duration histogram panel with a chart or empty state', async ({ page }) => {
                await expect(page.getByRole('heading', { name: 'Request duration histogram' })).toBeVisible();

                const panel = page.locator('.admin-metrics__panel', {
                    has: page.getByRole('heading', { name: 'Request duration histogram' }),
                });
                const hasChart = (await panel.locator('svg').count()) > 0;
                const hasEmptyState = (await panel.locator('.admin-metrics__empty').count()) > 0;
                expect(hasChart || hasEmptyState).toBe(true);
            });

            test('shows the queue stats panel with a table or empty state', async ({ page }) => {
                await expect(page.getByRole('heading', { name: 'Queue stats' })).toBeVisible();

                const table = page.locator('.admin-metrics__queue-table');
                const hasRows = (await table.locator('tbody tr').count()) > 0;
                const hasEmptyState = (await page.locator('.admin-metrics__panel', {
                    has: page.getByRole('heading', { name: 'Queue stats' }),
                }).locator('.admin-metrics__empty').count()) > 0;
                expect(hasRows || hasEmptyState).toBe(true);
            });

            test('queue stats table lists queue name, depth, and oldest job age when populated', async ({ page }) => {
                const table = page.locator('.admin-metrics__queue-table');
                const rowCount = await table.locator('tbody tr').count();
                test.skip(rowCount === 0, 'no queue metrics recorded yet in this environment');

                await expect(table.locator('thead th')).toHaveText(['Queue', 'Pending jobs', 'Oldest job age']);

                const firstRow = table.locator('tbody tr').first();
                await expect(firstRow.locator('td').nth(1)).toHaveText(/^\d+$/);
                await expect(firstRow.locator('td').nth(2)).toHaveText(/^[\d.]+s$/);
            });

            test('shows the slow requests panel with a table or empty state', async ({ page }) => {
                await expect(page.getByRole('heading', { name: 'Requests exceeding p95' })).toBeVisible();

                const table = page.locator('.admin-metrics__slow-table');
                const hasRows = (await table.locator('tbody tr').count()) > 0;
                const hasEmptyState = (await page.locator('.admin-metrics__panel', {
                    has: page.getByRole('heading', { name: 'Requests exceeding p95' }),
                }).locator('.admin-metrics__empty').count()) > 0;
                expect(hasRows || hasEmptyState).toBe(true);
            });

            test('slow requests are grouped by route with occurrence count, worst duration, and last-seen columns', async ({ page }) => {
                const table = page.locator('.admin-metrics__slow-table');
                const groupCount = await table.locator('.admin-metrics__slow-group-row').count();
                test.skip(groupCount === 0, 'no requests have exceeded p95 in this environment');

                await expect(table.locator('thead th').nth(1)).toHaveText('Route');
                await expect(table.locator('thead th').nth(2)).toHaveText('Occurrences');
                await expect(table.locator('thead th').nth(3)).toHaveText('Worst duration');
                await expect(table.locator('thead th').nth(4)).toHaveText('Last seen');

                const firstGroup = table.locator('.admin-metrics__slow-group-row').first();
                await expect(firstGroup.locator('td').nth(2)).toHaveText(/^\d+$/);
                await expect(firstGroup.locator('td').nth(3)).toHaveText(/^[\d.]+ms$/);

                // No detail rows before any group is expanded.
                await expect(table.locator('.admin-metrics__slow-detail-row')).toHaveCount(0);
            });

            test('clicking a route group expands individual requests, and clicking again collapses them', async ({ page }) => {
                const table = page.locator('.admin-metrics__slow-table');
                const groupCount = await table.locator('.admin-metrics__slow-group-row').count();
                test.skip(groupCount === 0, 'no requests have exceeded p95 in this environment');

                const firstGroup = table.locator('.admin-metrics__slow-group-row').first();
                const expectedCount = Number(await firstGroup.locator('td').nth(2).innerText());

                await firstGroup.click();
                await expect(firstGroup).toHaveAttribute('aria-expanded', 'true');

                const detailTable = table.locator('.admin-metrics__slow-detail-row').first().locator('.admin-metrics__slow-detail-table');
                await expect(detailTable.locator('thead th')).toHaveText(['Method', 'Status', 'Duration', 'Queries', 'Memory', 'p95 at time', 'When']);
                await expect(detailTable.locator('tbody tr')).toHaveCount(expectedCount);

                const firstDetailRow = detailTable.locator('tbody tr').first();
                await expect(firstDetailRow.locator('td').nth(0)).toHaveText(/^(GET|POST|PUT|PATCH|DELETE)$/);
                await expect(firstDetailRow.locator('td').nth(1)).toHaveText(/^\d{3}$/);
                await expect(firstDetailRow.locator('td').nth(2)).toHaveText(/^[\d.]+ms$/);
                await expect(firstDetailRow.locator('td').nth(3)).toHaveText(/^\d+$/);
                await expect(firstDetailRow.locator('td').nth(4)).toHaveText(/^[\d.]+MB$/);
                await expect(firstDetailRow.locator('td').nth(5)).toHaveText(/^[\d.]+ms$/);

                await firstGroup.click();
                await expect(firstGroup).toHaveAttribute('aria-expanded', 'false');
                await expect(table.locator('.admin-metrics__slow-detail-row')).toHaveCount(0);
            });

            test('filters slow requests by last-seen date, and clearing restores the full list', async ({ page }) => {
                const panel = page.locator('.admin-metrics__panel', {
                    has: page.getByRole('heading', { name: 'Requests exceeding p95' }),
                });
                const table = panel.locator('.admin-metrics__slow-table');
                const totalGroups = await table.locator('.admin-metrics__slow-group-row').count();
                test.skip(totalGroups === 0, 'no requests have exceeded p95 in this environment');

                const dateInput = panel.locator('#slow-requests-date');
                const today = new Date().toISOString().slice(0, 10);

                await dateInput.fill(today);
                await expect(table.locator('.admin-metrics__slow-group-row').first()).toBeVisible();

                // A date far in the past should match none of the recorded entries.
                await dateInput.fill('2000-01-01');
                await expect(panel.locator('.admin-metrics__empty')).toHaveText('No requests exceeded p95 on this date.');
                await expect(table).toHaveCount(0);

                await panel.getByRole('button', { name: 'Clear' }).click();
                await expect(dateInput).toHaveValue('');
                await expect(table.locator('.admin-metrics__slow-group-row')).toHaveCount(totalGroups);
            });

            test('breadcrumb links back to the admin panel', async ({ page }) => {
                await expect(page.getByRole('link', { name: 'Admin' })).toHaveAttribute('href', `${BASE}/admin`);
                await expect(page.getByText(breadcrumbLabel)).toBeVisible();
            });

            test('is reachable from the admin panel nav card', async ({ page }) => {
                await page.goto(`${BASE}/admin`);
                await page.getByRole('link', { name: navLinkName }).click();
                await expect(page).toHaveURL(`${BASE}${path}`);
            });
        });
    });
}

describeRequestMetricsPage('/admin/metrics/admin', 'Admin Request Metrics', /^Admin Request Metrics$/);
describeRequestMetricsPage('/admin/metrics/user', 'User Request Metrics', /^User Request Metrics$/);
