// Pruebas de navegador (npm run e2e). Levantan la app con una base SQLite propia: ver tests/e2e/servidor.sh
import { defineConfig, devices } from '@playwright/test';

const PORT = process.env.E2E_PORT || 8800;

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['list']] : 'list',
    use: { baseURL: `http://127.0.0.1:${PORT}`, trace: 'retain-on-failure', locale: 'es-EC', timezoneId: 'America/Guayaquil' },
    projects: [
        { name: 'escritorio', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
        { name: 'celular', use: { ...devices['Pixel 7'] } },
    ],
    webServer: {
        command: 'bash tests/e2e/servidor.sh',
        url: `http://127.0.0.1:${PORT}/login`,
        timeout: 180_000,
        reuseExistingServer: false,
        env: { E2E_PORT: String(PORT) },
    },
});
