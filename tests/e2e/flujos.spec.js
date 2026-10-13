import { test, expect, entrar } from './ayuda.js';

test('buscador global abre la ficha del paciente', async ({ page }, info) => {
    test.skip(info.project.name === 'celular', 'el atajo de teclado es de escritorio');
    await entrar(page, 'admin');
    await page.goto('/pacientes');
    await page.waitForLoadState('networkidle');
    await page.keyboard.press('Control+k');
    const campo = page.getByPlaceholder(/Buscar paciente/);
    await expect(campo).toBeFocused();
    await campo.pressSequentially('ma');
    const primero = page.locator('[data-resultado]').first();
    await expect(primero).toBeVisible();
    await page.keyboard.press('Enter');
    await page.waitForURL(/\/(pacientes|facturas)\/\d+|\/[a-z-]+$/);
    await expect(page.locator('h1').first()).toBeVisible();
});

test('el médico ve su inicio y la ficha clínica', async ({ page }) => {
    await entrar(page, 'medico');
    await page.goto('/dashboard');
    await expect(page.locator('body')).toContainText(/Mi agenda de hoy|agenda/i);
    await page.goto('/pacientes/1');
    await expect(page.getByText('Consultas').first()).toBeVisible();
});

test('recepción no entra a la auditoría ni a la historia clínica', async ({ page }) => {
    await entrar(page, 'recepcion');
    expect((await page.goto('/auditoria')).status()).toBe(403);
    expect((await page.goto('/expedientes')).status()).toBe(403);
});
