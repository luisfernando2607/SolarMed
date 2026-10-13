// Utilidades compartidas: cada prueba falla si hay errores de JavaScript o respuestas 5xx
import { test as base, expect } from '@playwright/test';

export const test = base.extend({
    page: async ({ page }, use) => {
        const errores = [];
        page.on('pageerror', (e) => errores.push(`JS: ${e.message}`));
        page.on('response', (r) => { if (r.status() >= 500) errores.push(`HTTP ${r.status()} ${r.url()}`); });
        // Algo bloqueado por la política de seguridad de contenido también es un error
        page.on('console', (m) => { if (/Content Security Policy|Refused to (load|execute|apply|connect|frame)/i.test(m.text())) errores.push(`CSP: ${m.text()}`); });
        await use(page);
        expect(errores, 'errores de JavaScript o del servidor').toEqual([]);
    },
});
export { expect };

export const USUARIOS = {
    admin: ['admin@clinica.ec', process.env.ADMIN_PASSWORD || 'Admin-E2e-2026'],
    medico: ['dr.garcia@clinica.ec', 'medico123'],
    recepcion: ['recepcion@clinica.ec', 'recepcion123'],
};

/** Inicia sesión y, si es el primer ingreso, acepta el acuerdo de confidencialidad */
export async function entrar(page, rol) {
    const [email, password] = USUARIOS[rol];
    await page.goto('/login');
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button[type=submit]');
    await page.waitForURL((u) => !u.pathname.startsWith('/login'), { waitUntil: 'commit' });
    if (await page.getByText('Acuerdo de confidencialidad').first().isVisible().catch(() => false)) {
        await page.locator('input[type=checkbox]').check();
        await page.getByRole('button', { name: /aceptar y continuar/i }).click();
        await page.waitForURL((u) => !u.pathname.startsWith('/cuenta'), { waitUntil: 'commit' });
    }
    await page.waitForLoadState('networkidle');
}

/** La página no se desborda a lo ancho (importante en el celular) */
export async function sinDesbordeLateral(page) {
    const desborda = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(desborda, `desborde lateral en ${page.url()}`).toBe(false);
}
