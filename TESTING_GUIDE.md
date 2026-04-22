Este documento describe la estrategia de pruebas implementada para garantizar la calidad, seguridad y rendimiento del sistema **ReadOut**.

## 🛠️ Instalación en una Nueva Máquina

Sigue estos pasos para preparar el entorno y asegurar que todas las pruebas pasen correctamente:

### 1. Clonar y Preparar el Backend
```bash
cd server-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite  # Opcional si usas :memory: en tests
php artisan migrate --seed      # Carga roles y permisos necesarios
```

### 2. Preparar el Frontend
```bash
cd ../client
npm install
```

### 3. Ejecutar la Suite de Pruebas
Una vez instaladas las dependencias, ya puedes correr los comandos de la sección siguiente. **Nota**: No necesitas tener GLPI corriendo para pasar los tests, pues la comunicación está simulada (mocked).

## 🚀 Cómo ejecutar las pruebas

### 1. Backend (Laravel + Pest PHP)
Todas las pruebas del backend se han migrado a **Pest PHP**. Pest ofrece una sintaxis mucho más limpia y expresiva. Se ejecutan en un entorno **SQLite en memoria** por defecto.

| Tipo de Prueba | Descripción | Comando |
| :--- | :--- | :--- |
| **Global** | Ejecuta todos los tests del sistema | `php artisan test` |
| **Caja Blanca** | Validación de lógica interna | `php artisan test --filter SecurityValidation` |
| **Seguridad** | Pruebas de IDOR y RBAC | `php artisan test --filter PrivilegeEscalation` |
| **Resistencia** | Carga masiva (2,000 registros) | `php artisan test --filter Stress` |
| **Rendimiento** | Benchmark de memoria y latencia | `php artisan test --filter Performance` |
| **Watch Mode** | Ejecuta tests automáticamente al guardar | `.\vendor\bin\pest --watch` |

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
