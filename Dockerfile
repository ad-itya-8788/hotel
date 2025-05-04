# Use official PHP image with Apache
FROM php:8.1-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

# Copy all project files into Apache directory
COPY . /var/www/html/

# Expose port 80 for Apache
EXPOSE 80

# Set the working directory
WORKDIR /var/www/html/

# Enable Apache mod_rewrite for clean URLs (if needed)
RUN a2enmod rewrite

# Set the ServerName to avoid the "Could not reliably determine the server's fully qualified domain name" warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Restart Apache to apply changes
CMD ["apache2-foreground"]
