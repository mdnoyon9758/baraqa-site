# Use the official, public Docker image for PHP with Apache
FROM php:8.2-apache

# Install necessary PHP extensions for PostgreSQL and other functions
RUN docker-php-ext-install pdo pdo_pgsql

# The default document root for this image is /var/www/html
# We will copy our code there
COPY . /var/www/html/

# Enable Apache's mod_rewrite for things like clean URLs (.htaccess)
RUN a2enmod rewrite