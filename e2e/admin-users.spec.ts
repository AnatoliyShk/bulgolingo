import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

type Role = { id: number; name: string; label: string };
type Row = { id: number; name: string; email: string; role_id: number; email_verified_at: string | null; created_at: string; role: Role };

const role = (id: number, name: string, label: string): Role => ({ id, name, label });

const USERS: Row[] = [
    { id: 9501, name: 'Ada Admin', email: 'ada@example.com', role_id: 2, email_verified_at: '2026-09-01T00:00:00Z', created_at: '2026-09-03T00:00:00Z', role: role(2, 'admin', 'Admin') },
    { id: 9502, name: 'Vic Visitor', email: 'vic@example.com', role_id: 3, email_verified_at: '2026-09-01T00:00:00Z', created_at: '2026-09-02T00:00:00Z', role: role(3, 'admin_visitor', 'Admin visitor') },
    { id: 9503, name: 'Stu Student', email: 'stu@example.com', role_id: 1, email_verified_at: null, created_at: '2026-09-01T00:00:00Z', role: role(1, 'student', 'Student') },
];

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

// Rewrites the users the server rendered, once the document is parsed and
// before the deferred app script mounts, so each role appears exactly once
// whatever accounts the database holds. The HTML response itself is left
// alone: fulfilling it would make Chrome refuse its scripts from Vite.
async function openUsers(page: Page, users: Row[] = USERS): Promise<void> {
    test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');

    await page.addInitScript((rows: Row[]) => {
        document.addEventListener('readystatechange', () => {
            const app = document.getElementById('app');

            if (document.readyState !== 'interactive' || !app?.dataset.page) {
                return;
            }

            const data = JSON.parse(app.dataset.page);

            if (data.component === 'Admin/Users/Index') {
                data.props.users = rows;
                app.dataset.page = JSON.stringify(data);
            }
        });
    }, users);

    await page.goto(`${BASE}/admin/users`);
}

const roleCell = (page: Page, name: string) => page.getByRole('row', { name: new RegExp(name) }).getByRole('cell').nth(2);

test.describe('Admin users list', () => {
    test('shows each user with their role label', async ({ page }) => {
        await openUsers(page);

        await expect(page.getByRole('columnheader', { name: 'Role' })).toBeVisible();
        await expect(page.locator('tbody tr')).toHaveCount(3);
        await expect(roleCell(page, 'Ada Admin')).toHaveText('Admin');
        await expect(roleCell(page, 'Vic Visitor')).toHaveText('Admin visitor');
        await expect(roleCell(page, 'Stu Student')).toHaveText('Student');
    });

    // Staff roles stand out as a badge; a student, the role almost every
    // row carries, is plain text so the badges are the ones that catch the eye.
    test('badges staff roles and leaves students as plain text', async ({ page }) => {
        await openUsers(page);

        await expect(roleCell(page, 'Ada Admin').locator('span.rounded-full')).toHaveText('Admin');
        await expect(roleCell(page, 'Vic Visitor').locator('span.rounded-full')).toHaveText('Admin visitor');
        await expect(roleCell(page, 'Stu Student').locator('span.rounded-full')).toHaveCount(0);
    });

    test('says so when there are no users', async ({ page }) => {
        await openUsers(page, []);

        await expect(page.getByText('No users yet.')).toBeVisible();
        await expect(page.locator('table')).toHaveCount(0);
    });

    // Not rewritten: the signed-in admin's own row comes from the real
    // server, which proves the controller sends the role with each user.
    test('labels the signed-in admin with the Admin role from the server', async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');

        await page.goto(`${BASE}/admin/users`);

        await expect(roleCell(page, ADMIN_EMAIL)).toHaveText('Admin');
    });
});
