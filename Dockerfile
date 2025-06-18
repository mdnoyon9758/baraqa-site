# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# --- 1. Install necessary system dependencies ---
# gd for image processing, pdo_pgsql and libpq-dev for PostgreSQL connection
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql

# --- 2. Enable Apache's mod_rewrite ---
# This is the crucial step for pretty URLs
RUN a2enmod rewrite

# --- 3. Set the working directory ---
WORKDIR /var/www/html

# --- 4. Copy application files ---
# Copy the .htaccess file first so it gets the correct permissions
COPY .htaccess .
# Copy the rest of the application code
COPY . .

# --- 5. Fix permissions for Apache ---
# Ensure the web server has permission to write to necessary folders
RUN chown -R www-data:www-data /var/www/html/public/images \
    && chown -R www-data:www-data /var/www/html/jobs/logs
RUN chmod -R 775 /var/www/html/public/images \
    && chmod -R 775 /var/www/html/jobs/logs

# The following line is from the default php:apache image.
# It tells Apache how to handle requests. It's good practice to keep it.
# You don't need to add this if it's not already there. The base image handles it.
# EXPOSE 80