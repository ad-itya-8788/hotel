FROM php:8.1-apache

# Copy all project files into Apache directory
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
