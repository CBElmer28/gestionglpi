# Sistema de Biblioteca - Proyecto Integrado

Este repositorio contiene el sistema completo de gestión de biblioteca, incluyendo el frontend, el backend administrativo y la integración con el inventario GLPI.

## Estructura del Proyecto

- **`/client`**: Aplicación Frontend desarrollada en Vue.js.
- **`/server-laravel`**: API Backend desarrollada en Laravel 10.
- **`/server-glpi`**: Entorno Dockerizado con GLPI y su respectiva base de datos MariaDB.
- **`/db_backups`**: Exportaciones SQL con los datos actuales (libros, usuarios, activos).

## Requisitos Previos

1. **Docker y Docker Compose** (para GLPI).
2. **PHP >= 8.1** y **Composer** (para Laravel).
3. **Node.js >= 18** y **NPM/PNPM** (para el Cliente).
4. **Servidor MySQL/MariaDB** local (ej. XAMPP o Laragon) para la base de datos de Laravel.

## Pasos para la Restauración

### 1. Clonar y configurar variables de entorno
Crea los archivos `.env` en `/client` y `/server-laravel` basándote en los archivos de configuración actuales.

### 2. Levantar GLPI (Docker)
```bash
cd server-glpi
docker-compose up -d
```

### 3. Restaurar Bases de Datos
Para no perder los libros y usuarios existentes, importa los dumps incluidos:

**Para Laravel (Base de datos: `biblioteca`):**
Importa `db_backups/laravel_biblioteca.sql` en tu MySQL local.

**Para GLPI (Servicio Docker):**
```bash
# Una vez que el contenedor glpi-db esté arriba:
docker exec -i glpi-db mysql -u glpi_user -pglpi_pass glpi_db < db_backups/glpi_db.sql
```

### 4. Instalar dependencias
```bash
# Server Laravel
cd server-laravel
composer install
php artisan key:generate

# Client
cd client
npm install
```

## Credenciales por defecto
- **Admin Laravel**: admin@biblioteca.com / admin123
- **GLPI**: glpi / glpi (usuario por defecto del sistema)
