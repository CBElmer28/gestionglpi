import { test, expect } from '@playwright/test';
import { allure } from "allure-playwright";

test.describe('Flujo de la Biblioteca Digital', () => {
  // Configuración de timeouts globales para esta suite
  test.setTimeout(120000); // 2 minutos máximo

  test.beforeEach(async ({ page }) => {
    // Escuchar logs de consola del navegador para depuración
    page.on('console', msg => console.log(`E2E-LOG [${msg.type()}]: ${msg.text()}`));

    allure.epic('E2E: Flujos de Usuario Final');
    allure.feature('Experiencia de Usuario en Biblioteca');
  });

  test('Autenticación y Navegación al Dashboard', async ({ page }) => {
    allure.story('Inicio de Sesión y Seguridad');
    allure.description('Valida que los usuarios puedan autenticarse correctamente y sean redirigidos según su rol.');
    allure.severity('critical');

    // 1. Ir a la página de login
    await page.goto('/login');
    await expect(page).toHaveTitle(/Biblioteca Digital/i);

    // 2. Intentar login con credenciales inválidas
    await page.fill('#email', 'fake@user.com');
    await page.fill('#password', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Debería aparecer la alerta (o al menos no redirigir)
    const errorAlert = page.locator('.login-error-alert');
    await expect(errorAlert).toBeVisible();

    // 3. Login correcto como Admin
    await page.fill('#email', 'admin@biblioteca.com');
    await page.fill('#password', 'admin123');
    await page.click('button[type="submit"]');

    // 4. Verificar redirección
    await expect(page).toHaveURL(/.*dashboard/);
    await expect(page.locator('.topbar-title')).toContainText('Dashboard');
  });

  test('Sincronización y Gestión de Libros', async ({ page }) => {
    allure.story('Operaciones de Inventario');
    allure.description('Cubre el flujo completo desde la sincronización con GLPI hasta la creación de un nuevo ejemplar.');
    allure.severity('critical');

    await page.goto('/login');
    await page.fill('#email', 'admin@biblioteca.com');
    await page.fill('#password', 'admin123');
    await page.click('button[type="submit"]');

    await page.click('.sidebar-item:has-text("Libros")');
    await expect(page).toHaveURL(/.*books/);

    // 1. Sincronizar (opcional)
    const syncBtn = page.locator('button').filter({ hasText: 'Sincronizar' });
    if (await syncBtn.isVisible()) {
      await syncBtn.click();
      await expect(page.locator('button:has-text("Sincronizando")')).not.toBeVisible({ timeout: 60000 });
    }

    // 2. Crear nuevo libro
    await page.click('#btn-new-book');
    await expect(page.locator('#modal-book')).toBeVisible();

    const bookTitle = `E2E Book ${Date.now()}`;
    // Generar un ISBN-13 válido (978 + 10 dígitos)
    const bookIsbn = `978${Math.floor(Math.random() * 10000000000).toString().padStart(10, '0')}`;

    await page.fill('#modal-book input[placeholder="Título del libro"]', bookTitle);
    await page.fill('#modal-book input[placeholder="Nombre del autor"]', 'Playwright Author');
    await page.fill('#modal-book input[placeholder="978-XXXXXXXXXX"]', bookIsbn);
    await page.fill('#modal-book input[placeholder="Ej: 2da Edición / 2024"]', '1ra Edición E2E');
    await page.fill('#modal-book textarea', 'Descripción de prueba generada por E2E.');

    // Seleccionar Género (esperar a que haya opciones reales)
    await page.locator('.form-group:has-text("Género") .form-control').click();
    const generoOpciones = page.locator('.combobox-item');
    await expect(generoOpciones.first()).toBeVisible({ timeout: 15000 });
    // Hacemos click en la primera opción que NO sea un placeholder (si lo hay)
    await generoOpciones.first().click();

    // Seleccionar Editorial
    await page.locator('.form-group:has-text("Editorial") .form-control').click();
    const editorialOpciones = page.locator('.combobox-item');
    await expect(editorialOpciones.first()).toBeVisible({ timeout: 15000 });
    await editorialOpciones.first().click();

    // 3. Guardar
    await page.click('#btn-save-book');

    // 3. Verificar éxito
    await expect(page.locator('.Vue-Toastification__toast--success')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('#modal-book')).toBeHidden({ timeout: 10000 });

    // 4. Verificar presencia en tabla
    await expect(page.locator('table')).toContainText(bookTitle);
  });

  test('Restricciones de Rol (Bibliotecario)', async ({ page }) => {
    allure.story('Validación de Autorización');
    allure.description('Verifica que el rol de bibliotecario tenga las restricciones correctas en la interfaz de usuario.');
    allure.severity('normal');

    // Login como bibliotecario
    await page.goto('/login');
    await page.fill('#email', 'bibliotecario@biblioteca.com');
    await page.fill('#password', 'biblio123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*dashboard/);

    // Ir a libros
    await page.click('.sidebar-item:has-text("Libros")');

    // Esperar a que la tabla o empty state carguen
    await expect(page.locator('table, .empty-state').first()).toBeVisible({ timeout: 10000 });

    // ELIMINAR no debería estar visible en la tabla para este rol
    const deleteBtn = page.locator('button[title="Eliminar"]');
    await expect(deleteBtn).not.toBeVisible();
  });


  test('Reporte de Incidencia a GLPI', async ({ page }) => {
    allure.story('Soporte y Mantenimiento');
    allure.description('Verifica que un usuario pueda reportar un problema con un libro y que este se envíe a GLPI.');
    allure.severity('critical');

    // Login como administrador
    await page.goto('/login');
    await page.fill('#email', 'admin@biblioteca.com');
    await page.fill('#password', 'admin123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*dashboard/);

    // Navegar a Libros
    await page.click('.sidebar-item:has-text("Libros")');
    
    // Buscar la primera fila que TENGA el botón de reporte (evitando los que ya están en mantenimiento)
    const reportButton = page.locator('.btn-action-report').first();
    await expect(reportButton).toBeVisible({ timeout: 15000 });
    await reportButton.click();

    // Verificar que el modal de reporte esté visible
    await expect(page.locator('#modal-report')).toBeVisible();

    // Llenar el formulario de incidencia
    // Seleccionar Prioridad (Alta)
    await page.locator('.form-group:has-text("Prioridad") .form-control').click();
    await page.locator('.combobox-item:has-text("Alta")').click();

    // Llenar descripción
    await page.fill('textarea[placeholder*="Ej: El libro tiene varias hojas sueltas"]', 'Incidencia E2E - Verificación de flujo GLPI - Portada dañada.');

    // Enviar a GLPI
    await page.click('button:has-text("Enviar a GLPI")');

    // Verificar éxito (damos 60s porque el backend hace ~8 llamadas API + Envío de Email)
    // Buscamos el Toast de éxito por su texto característico
    const successToast = page.locator('text=Gracias por reportar esta incidencia');
    await expect(successToast).toBeVisible({ timeout: 60000 });
    
    // También verificamos que el modal se haya cerrado automáticamente
    await expect(page.locator('#modal-report')).toBeHidden({ timeout: 20000 });
  });
});
