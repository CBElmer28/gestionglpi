# Plan Maestro de Pruebas - Sistema de Biblioteca

Este documento describe la estrategia de pruebas para garantizar la calidad y estabilidad de la integración entre Laravel, Vue.js y GLPI.

## 1. Estrategia de Pruebas

### A. Pruebas de Caja Blanca (Unitarias)
**Objetivo**: Validar la lógica interna y el manejo de errores en el código PHP.
- **Servicios**: `GlpiService`, `BookService`.
- **Mocks**: Se utiliza `Http::fake()` para simular la API de GLPI.
- **Casos**: 
  - Construcción de cabeceras.
  - Manejo de excepciones (caída de Docker).
  - Lógica de sincronización (POST vs PUT).

### B. Pruebas de Caja Gris (Funcionalidad)
**Objetivo**: Validar la integración entre la base de datos y la lógica de negocio.
- **CRUD de Libros**: Verificar que los datos se guarden correctamente en MySQL y se limpien las relaciones al borrar.
- **RBAC (Roles)**: Asegurar que los perfiles `admin` y `bibliotecario` tengan los permisos adecuados.

### C. Pruebas de Caja Negra (Manuales/Visuales)
**Objetivo**: Validar la experiencia de usuario final y la visibilidad en GLPI.
- **Guía de Verificación**: [ManualVerificationGuide](tests/ManualVerificationGuide.md).
- **Dashboard Vue**: Verificar que los cambios se reflejen visualmente.
- **Dashboard GLPI**: Confirmar la aparición de tickets y activos.

---

## 2. Cómo Ejecutar las Pruebas

### Pruebas de Backend (Laravel)
Para ejecutar todas las pruebas automatizadas:
```bash
php artisan test
```

### Pruebas de Frontend (Vue.js + Vitest)
Para ejecutar las pruebas del cliente y ver la cobertura:
```bash
# Dentro de la carpeta /client
npm run test      # Ejecución normal
npm run coverage  # Reporte de cobertura detallado
```

---

## 3. Matriz de Roles y Permisos (RBAC)

| Módulo | Admin | Bibliotecario | Lector (Futuro) |
| :--- | :---: | :---: | :---: |
| Crear Libros | ✅ | ✅ | ❌ |
| Editar Libros | ✅ | ✅ | ❌ |
| Borrar Libros | ✅ | ❌ | ❌ |
| Crear Tickets | ✅ | ✅ | ✅ |
| Ver Dashboard | ✅ | ✅ | ✅ |

---

## 4. Simulaciones y Entorno
*   **Backend**: Utiliza `SQLite` en memoria y `Http::fake()`.
*   **Frontend**: Utiliza `happy-dom` (entorno ligero de navegador) y mocks de `Axios`, `Pinia` y `Vue Router`.

---

## 5. Resultados de Cobertura (Última Ejecución)

### Frontend (Vitest)
- **General**: 75% de líneas cubiertas.
- **Lógica de Autenticación (`authService.js`)**: 100%.
- **Vistas de Login (`LoginView.vue`)**: 97.5%.
- **Estado Global (`auth.js`)**: 75%.

> [!TIP]
> Para mantener la calidad, se recomienda que toda nueva funcionalidad en el frontend incluya su archivo `.test.js` correspondiente al lado del componente o servicio.
