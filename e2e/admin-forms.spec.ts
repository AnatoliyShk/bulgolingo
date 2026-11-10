import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

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

type FormCase = {
    name: string;
    index: string;
    labels: string[];
    createButton: string;
    required: string[];
    editId?: string;
    editMethod: 'PATCH' | 'PUT';
};

// One entry per shared admin form (BotForm, MessengerForm, LessonForm,
// LearningPathForm, ScriptedDialogueForm). An edit page is opened by its
// fixture id when e2e:fixture-env exports one, else through the index's first
// Edit link.
const forms: FormCase[] = [
    {
        name: 'bot',
        index: '/admin/bots',
        labels: ['Name', 'Description'],
        createButton: 'Create Bot',
        required: ['name', 'description'],
        editMethod: 'PATCH',
    },
    {
        name: 'messenger',
        index: '/admin/messengers',
        labels: ['User', 'Messenger Name', 'Messenger User ID'],
        createButton: 'Create Messenger',
        required: ['user id', 'messenger name'],
        editMethod: 'PATCH',
    },
    {
        name: 'lesson',
        index: '/admin/lessons',
        labels: ['Name', 'Description'],
        createButton: 'Create Lesson',
        required: ['name', 'description'],
        editId: process.env.E2E_LESSON_ID,
        editMethod: 'PATCH',
    },
    {
        name: 'learning path',
        index: '/admin/learning-paths',
        labels: ['Name', 'Language', 'Type', 'Level'],
        createButton: 'Create Path',
        required: ['name', 'language'],
        editId: process.env.E2E_FIRST_PATH_ID,
        editMethod: 'PUT',
    },
    {
        name: 'scripted dialogue',
        index: '/admin/scripted-dialogues',
        labels: ['Bot', 'User'],
        createButton: 'Create Dialogue',
        required: ['bot id', 'user id'],
        editId: process.env.E2E_SCRIPTED_DIALOGUE_ID,
        editMethod: 'PATCH',
    },
];

const form = (page: Page) => page.locator('form.admin-form__body');

// Opens the edit page and returns false when the section has no record to edit.
async function openEdit(page: Page, c: FormCase): Promise<boolean> {
    if (c.editId) {
        await page.goto(`${BASE}${c.index}/${c.editId}/edit`);
        return true;
    }

    await page.goto(`${BASE}${c.index}`);
    const edit = page.locator('table').getByRole('link', { name: 'Edit', exact: true }).first();

    if ((await edit.count()) === 0) return false;

    await edit.click();
    await page.waitForURL(/\/edit$/);
    return true;
}

test.describe('Admin shared forms', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
    });

    for (const c of forms) {
        test(`the ${c.name} create form links every label to its field`, async ({ page }) => {
            await page.goto(`${BASE}${c.index}/create`);

            for (const label of c.labels) {
                await expect(form(page).getByLabel(label, { exact: true })).toBeVisible();
            }
            await expect(form(page).getByRole('button', { name: c.createButton })).toBeEnabled();
            await expect(form(page).getByRole('link', { name: 'Cancel' })).toHaveAttribute('href', new RegExp(`${c.index}$`));
        });

        test(`the ${c.name} create form shows the server errors for an empty submission`, async ({ page }) => {
            await page.goto(`${BASE}${c.index}/create`);
            await form(page).getByRole('button', { name: c.createButton }).click();

            for (const field of c.required) {
                await expect(form(page).locator('.admin-form__error', { hasText: new RegExp(`^The ${field} field`) })).toBeVisible();
            }
            await expect(page).toHaveURL(`${BASE}${c.index}/create`);
        });

        test(`the ${c.name} edit form opens filled in and saves with ${c.editMethod}`, async ({ page }) => {
            test.skip(!(await openEdit(page, c)), `no ${c.name} to edit in this environment`);

            for (const label of c.labels) {
                await expect(form(page).getByLabel(label, { exact: true })).toBeVisible();
            }
            await expect(form(page).getByLabel(c.labels[0], { exact: true })).not.toHaveValue('');
            await expect(form(page).getByRole('button', { name: c.createButton })).toHaveCount(0);

            const request = page.waitForRequest((r) => r.method() === c.editMethod);
            await form(page).getByRole('button', { name: 'Save', exact: true }).click();
            await request;

            await expect(form(page).locator('.admin-form__error')).toHaveCount(0);
        });
    }
});
