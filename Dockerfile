# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # --- CHANGE 1: Added 'pgsql' to the list of extensions to install ---
    && docker-php-ext-install gd pdo pdo_pgsql pgsql

# Enable the rewrite module in Apache
RUN a2enmod rewrite

# CRITICAL STEP: Replace the default Apache site configuration with our custom one.
# This makes our rewrite rules and security settings active.
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create directories for writable content and ensure they exist
# --- CHANGE 2: Added -p to ensure parent directories are created if needed ---
RUN mkdir -p public/images/products \
             public/images/categories \
             public/images/brands \
             public/images/galleries \
             public/fonts \
             jobs/logs

# Set correct permissions for the Apache user
RUN chown -R www-data:www-data /var/www/html

# The default Apache command will run, so no need for CMD or EXPOSE