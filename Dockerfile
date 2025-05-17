# Use official PHP image with Apache
FROM php:8.1-apache

# Install PostgreSQL extension and clean up apt cache
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for clean URLs (optional but common)
RUN a2enmod rewrite

# Set the ServerName to avoid warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy all project files to Apache's document root
COPY . /var/www/html/

# Set the working directory (not strictly necessary but good practice)
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
