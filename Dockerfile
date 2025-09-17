# Usamos PHP con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite para URLs amigables (opcional)
RUN a2enmod rewrite

# Copiar todos los archivos de la API al contenedor
COPY . /var/www/html/

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html/

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]
