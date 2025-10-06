# Base image with Apache and PHP 8.2
FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy frontend files (public and auth)
COPY public/ /var/www/html/
COPY auth/ /var/www/html/auth/

# Copy backend (API + configs + helpers)
COPY api/ /var/www/api/
COPY config/ /var/www/config/
COPY helpers/ /var/www/helpers/

# If you have composer libs
COPY composer.json composer.lock* /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader || true

# Expose port 8080 (for Railway)
EXPOSE 8080

# Change Apache port 80 -> 8080
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides
RUN echo '<Directory "/var/www/html">\nAllowOverride All\n</Directory>' > /etc/apache2/conf-available/override.conf && a2enconf override

# Start Apache
CMD ["apache2-foreground"]
