# Start with the official PHP 8.2 image that includes Apache
FROM php:8.2-apache

# --- 1. System Dependencies ---
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql

# --- 2. Apache Configuration ---
# Enable mod_rewrite module
RUN a2enmod rewrite

# CRITICAL FIX: Copy our custom Apache config file to enable .htaccess
# This is a more reliable method than using 'sed'.
COPY apache-config.conf /etc/apache2/conf-available/override.conf
RUN a2enconf override

# --- 3. Application Setup ---
WORKDIR /var/www/html
COPY . .

# --- 4. Create Necessary Directories ---
RUN mkdir -p public/images \
    && mkdir -p public/fonts \
    && mkdir -p jobs/logs

# --- 5. Set Permissions ---
RUN chown -R www-data:www-data /var/www/html/public/images \
    && chown -R www-data:www-data /var/www/html/public/fonts \
    && chown -R www-data:www-data /var/www/html/jobs/logs