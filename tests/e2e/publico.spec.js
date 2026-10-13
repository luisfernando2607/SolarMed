import { test, expect, sinDesbordeLateral } from './ayuda.js';

test('portada pública y agenda en línea', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('body')).toContainText(/agendar|turno/i);
    await sinDesbordeLateral(page);

    await page.goto('/agendar');
    await expect(page.locator('body')).toContainText(/especialidad/i);
    await sinDesbordeLateral(page);

    await page.goto('/privacidad');
    await expect(page.getByRole('heading', { name: 'Aviso de privacidad' })).toBeVisible();
    await sinDesbordeLateral(page);
});

test('login en español y enlaces inválidos', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByText('Recordarme')).toBeVisible();

    const r = await page.goto('/encuesta/' + 'a'.repeat(40));
    expect(r.status()).toBe(404);
});
