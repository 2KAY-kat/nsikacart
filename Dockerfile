# Use official PHP with Apache
FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite for clean URLs
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy frontend files
COPY public/ /var/www/html/
COPY auth/ /var/www/html/auth/

# Copy backend and config (not public)
COPY api/ /var/www/api/
COPY config/ /var/www/logs/
COPY helpers/ /var/www/helpers/

# Expose Railway port
EXPOSE 8080

# Change Apache port from 80 to 8080
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Allow .htaccess override for public folder
RUN echo '<Directory "/var/www/html">\nAllowOverride All\n</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Start Apache
CMD ["apache2-foreground"]
