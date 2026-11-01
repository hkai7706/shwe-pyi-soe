import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/Browser',
    timeout: 180000,
    workers: 1,
    use: {
        baseURL: process.env.APP_URL || 'http://127.0.0.1:8000',
        launchOptions: { executablePath: process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true },
    },
});
