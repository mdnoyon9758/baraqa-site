# Use the official, public Docker image for PHP with Apache
FROM php:8.2-apache

# Install system dependencies needed for PostgreSQL PHP extension
# We first run 'apt-get update' and then install 'libpq-dev' and 'python3'
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql

# The default document root for this image is /var/www/html
# We will copy our code there
COPY . /var/www/html/

# Enable Apache's mod_rewrite for things like clean URLs (.htaccess)
RUN a2enmod rewrite