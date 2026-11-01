import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
const localEnv = Object.fromEntries(readFileSync('.env', 'utf8').split(/\r?\n/).filter(line => line && !line.startsWith('#')).map(line => {
    const index = line.indexOf('=');
    return [line.slice(0, index), line.slice(index + 1).replace(/^"|"$/g, '')];
}));
const staffPath = process.env.ADMIN_PATH || localEnv.ADMIN_PATH || 'staff';
const publicPaths = ['/', '/announcements', '/grades', '/grades/6', '/teachers', '/top-achievers', '/students/1', '/overview'];
async function checkPage(page, path) {
    const response = await page.goto(path);
    expect(response.status(), path).toBe(200);
    await page.evaluate(() => document.fonts.ready);
    const widths = await page.evaluate(() => ({ viewport: innerWidth, page: document.documentElement.scrollWidth }));
    expect(widths.page, `Overflow on ${path}`).toBeLessThanOrEqual(widths.viewport);
    expect(await page.locator('body').innerText()).not.toMatch(/ui\.[a-z_]+/);
}
for (const width of [1440, 390]) {
    test(`Public and staff pages fit ${width}px in both languages`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setViewportSize({ width, height: 1000 });
        for (const locale of ['en','my']) {
            await page.goto('/');
            if (width === 390) { await page.locator('.mobile-menu').click(); }
            await page.locator(`.sidebar form[action$="/language/${locale}"] button`).click();
            await expect(page.locator('html')).toHaveAttribute('lang', locale);
            for (const path of publicPaths) {
                await checkPage(page, path);
                await expect(page.locator(`a[href*="/${staffPath}"]`)).toHaveCount(0);
            }
            if (locale === 'en') {
                await page.goto('/');
                await page.screenshot({ path: `test-results/home-${width}.png`, fullPage: true });
            }
        }
        await page.goto(`/${staffPath}/login`);
        await page.locator('input[name=username]').fill(process.env.ADMIN_USERNAME || localEnv.ADMIN_USERNAME || 'schooladmin');
        await page.locator('input[name=password]').fill(process.env.ADMIN_PASSWORD || localEnv.ADMIN_PASSWORD);
        await page.locator('button[type=submit], form > button.button').first().click();
        await expect(page).toHaveURL(new RegExp(`/${staffPath}$`));
        for (const locale of ['en', 'my']) {
            if (width === 390) { await page.locator('.mobile-menu').click(); }
            await page.locator(`.sidebar form[action$="/language/${locale}"] button`).click();
            for (const path of ['', '/students', '/teachers', '/announcements', '/attendance', '/marks', '/fees', '/settings', '/students/create', '/teachers/create', '/announcements/create']) {
            await checkPage(page, `/${staffPath}${path}`);
        }
        }
        expect(errors).toEqual([]);
    });
}
