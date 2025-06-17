# Use the official Render PHP-Apache image
FROM render/php:8.2-apache

# Set the document root to the public directory in the image
# The default is /var/www/html, we copy our code there
WORKDIR /var/www/html

# Copy all application files from the current directory into the container
COPY . .

# The base image already configures Apache, so no further commands are needed.
# It will automatically start Apache when the container runs.