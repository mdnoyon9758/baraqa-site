# Start with the official PHP 8.2 image that includes Apache
FROM php:8.2-apache

# --- 1. System Dependencies ---
# Install libraries needed for PHP extensions (gd for images, pdo_pgsql for database)
# Also install zip/unzip which are useful utilities.
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
# Enable mod_rewrite to allow .htaccess files to work for pretty URLs
RUN a2enmod rewrite

# --- 3. Application Setup ---
# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all your application files into the working directory
COPY . .

# --- 4. Create Necessary Directories ---
# This is the critical fix. Create directories before trying to set permissions on them.
# The -p flag ensures parent directories are created if they don't exist.
RUN mkdir -p public/images \
    && mkdir -p public/fonts \
    && mkdir -p jobs/logs

# --- 5. Set Permissions ---
# Set the Apache user (www-data) as the owner of the directories
# where your application needs to write files (e.g., generated images, logs).
# This allows PHP to create files in these folders.
RUN chown -R www-data:www-data /var/www/html/public/images \
    && chown -R www-data:www-data /var/www/html/public/fonts \
    && chown -R www-data:www-data /var/www/html/jobs/logs

# The base image (php:8.2-apache) already handles exposing port 80 and starting Apache.
# No need for an EXPOSE or CMD command unless you want to override the default.