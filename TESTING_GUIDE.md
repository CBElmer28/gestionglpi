# Guía de Pruebas — Sistema ReadOut

Este documento describe la estrategia de pruebas implementada para garantizar la calidad, seguridad y rendimiento del sistema **ReadOut**.

## 🚀 Cómo ejecutar las pruebas

### 1. Backend (Laravel)
Todas las pruebas del backend se ejecutan en un entorno **SQLite en memoria** por defecto, por lo que son rápidas y seguras (no afectan a tu base de datos local).

| Tipo de Prueba | Descripción | Comando de Ejecución |
| :--- | :--- | :--- |
| **Global** | Ejecuta todos los tests del sistema | `php artisan test` |
| **Caja Blanca** | Validación de lógica interna y caminos críticos | `php artisan test --filter SecurityValidationTest` |
| **Integración** | Flujos completos de préstamos y GLPI | `php artisan test --filter LoanIntegrationTest` |
| **Recuperación** | Pruebas de resiliencia y auto-curación | `php artisan test --filter RecoveryTest` |
| **Seguridad** | Pruebas de IDOR y control de accesos (RBAC) | `php artisan test --filter PrivilegeEscalationTest` |
| **Resistencia** | Carga masiva (2,000 registros) | `php artisan test --filter StressTest` |
| **Rendimiento** | Benchmarking de memoria y latencia | `php artisan test --filter PerformanceTest` |

### 2. Frontend (Vue.js)
El frontend utiliza **Vitest** para pruebas unitarias y de componentes.

- **Ejecutar todas las pruebas**:
  ```bash
  cd client
  npm run test
  ```
- **Generar reporte de cobertura**:
  ```bash
  npm run coverage
  ```

---

## 📂 Descripción de las Pruebas Implementadas

### 🛡️ Pruebas de Seguridad (Security)
- **IDOR**: Verifica que un usuario no pueda acceder a datos de otros cambiando IDs en la URL.
- **RBAC**: Valida que los roles (Lector, Bibliotecario, Admin) tengan acceso exclusivo a sus funciones autorizadas.
- **Sanitización**: Comprueba que los filtros bloqueen etiquetas `<script>` y patrones de SQL.

### 🔄 Pruebas de Recuperación (Recovery)
- **Token Self-Healing**: Si el token de sesión con GLPI falla, el sistema lo invalida y obtiene uno nuevo de forma automática.
- **Timeout Handling**: Maneja esperas prolongadas del servidor externo sin colapsar la aplicación local.

### 🏎️ Pruebas de Rendimiento (Performance)
- **Memory Profiling**: Asegura que el consumo de RAM sea eficiente al procesar catálogos grandes.
- **Latency Benchmarking**: Mide los tiempos de respuesta de los servicios críticos (promedios detectados: ~20ms - 100ms).

### 🧪 Pruebas de Caja Blanca (White-Box)
- Basadas en el análisis del flujo de control de funciones clave del controlador de libros y préstamos, garantizando que cada "if/else" y "catch" haya sido probado al menos una vez.

---
**El sistema ReadOut está totalmente verificado y listo para su uso.**
