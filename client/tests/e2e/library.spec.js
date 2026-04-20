import { test, expect } from '@playwright/test';

test.describe('Flujo de la Biblioteca Digital', () => {
  // Configuración de timeouts globales para esta suite
  test.setTimeout(120000); // 2 minutos máximo

  test.beforeEach(async ({ page }) => {
    // Escuchar logs de consola del navegador para depuración
    page.on('console', msg => console.log(`E2E-LOG [${msg.type()}]: ${msg.text()}`));
  });

  test('Autenticación y Navegación al Dashboard', async ({ page }) => {
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
      // Esperamos a que el texto "Sincronizando..." desaparezca
      await expect(page.locator('button:has-text("Sincronizando")')).not.toBeVisible({ timeout: 60000 });
    }

    // 2. Crear nuevo libro
    await page.click('#btn-new-book');
    await expect(page.locator('#modal-book')).toBeVisible();

    const bookTitle = `E2E Book ${Date.now()}`;
    const bookIsbn = `978-${Math.floor(Math.random() * 10000000000)}`;
    await page.fill('#modal-book input[placeholder="Título del libro"]', bookTitle);
    await page.fill('#modal-book input[placeholder="Nombre del autor"]', 'Playwright Author');
    await page.fill('#modal-book input[placeholder="978-XXXXXXXXXX"]', bookIsbn);
    await page.fill('#modal-book textarea', 'Descripción de prueba.');

    // Seleccionar Género (Usando el componente BaseCombobox)
    await page.locator('.form-group:has-text("Género") .form-control').click();
    await expect(page.locator('.combobox-item').first()).toBeVisible({ timeout: 10000 });
    await page.locator('.combobox-item').first().click();

    // Seleccionar Editorial (Usando el componente BaseCombobox)
    await page.locator('.form-group:has-text("Editorial") .form-control').click();
    await expect(page.locator('.combobox-item').first()).toBeVisible({ timeout: 10000 });
    await page.locator('.combobox-item').first().click();

    // 3. Guardar
    await page.click('#btn-save-book');

    // 3. Verificar éxito
    await expect(page.locator('.Vue-Toastification__toast--success')).toBeVisible({ timeout: 10000 });
    
    // Esperar a que el modal desaparezca totalmente
    await expect(page.locator('#modal-book')).toBeHidden({ timeout: 10000 });

    // 4. Verificar presencia en tabla
    await expect(page.locator('table')).toContainText(bookTitle);
  });

  test('Restricciones de Rol (Bibliotecario)', async ({ page }) => {
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
});
