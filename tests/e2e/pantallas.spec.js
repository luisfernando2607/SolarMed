import { test, expect, entrar, sinDesbordeLateral } from './ayuda.js';

// Todas las pantallas del administrador cargan sin errores (y sin desborde lateral en el celular)
const PANTALLAS = [
    '/admin/dashboard', '/pacientes', '/pacientes/1', '/expedientes', '/agenda', '/sala-espera', '/turnos/crear',
    '/referencias', '/facturas', '/facturas/crear', '/caja', '/reportes/ventas', '/reportes/morbilidad', '/reportes/satisfaccion',
    '/auditoria/panel', '/auditoria', '/auditoria/calidad-hc', '/auditoria/planes-accion', '/auditoria/solicitudes', '/auditoria/incidentes',
    '/usuarios', '/roles', '/especialidades', '/tarifario', '/configuracion', '/sistema', '/sri/configuracion',
    '/consentimientos/plantillas', '/inventario', '/whatsapp', '/telemedicina', '/ayuda', '/profile',
];

test('pantallas del administrador', async ({ page }, info) => {
    test.setTimeout(180_000);
    await entrar(page, 'admin');
    for (const ruta of PANTALLAS) {
        const r = await page.goto(ruta);
        expect(r.status(), ruta).toBeLessThan(400);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('main h1, main h2').first(), ruta).toBeVisible();
        if (info.project.name === 'celular') {
            await sinDesbordeLateral(page);
        }
    }
});
