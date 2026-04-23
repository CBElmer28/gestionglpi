# Usamos una imagen oficial de PHP con Apache incluido. 
# PHP 8.2 o superior es ideal para asegurar compatibilidad con versiones recientes como Laravel 11.
FROM php:8.2-apache

# 1. Habilitar mod_rewrite de Apache (crucial para el enrutador de Laravel)
RUN a2enmod rewrite

# 2. Instalar dependencias del sistema necesarias para Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Limpiar caché de apt para reducir el tamaño de la imagen final
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Instalar extensiones de PHP requeridas
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. Obtener Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Configurar el directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# 6. Copiar los archivos de tu proyecto al contenedor
COPY . .

# 7. Instalar las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# 8. Asignar los permisos correctos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Configurar VirtualHost y habilitar .htaccess (LA SOLUCIÓN AQUÍ)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 10. Exponer el puerto web
EXPOSE 80