# ============================
# Nsikacart Dockerfile (Railway)
# ============================
FROM php:8.2-apache

# Install system packages required for curl/zip/git and build PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl zip \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for SPA routing
RUN a2enmod rewrite

# Set working directory to /var/www for composer install
WORKDIR /var/www

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install Composer and project dependencies (no-dev for smaller image)
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set webroot and copy frontend
WORKDIR /var/www/html
COPY public/ /var/www/html/
COPY auth/ /var/www/html/auth/

# Copy backend & helpers (API outside public)
COPY api/ /var/www/api/
COPY helpers/ /var/www/helpers/
COPY logs/ /var/www/logs/

# vendor/ was created by composer install at /var/www/vendor (keep it)
# ensure logs folder permissions
RUN mkdir -p /var/www/logs \
    && chown -R www-data:www-data /var/www/logs \
    && chmod -R 775 /var/www/logs \
    && chown -R www-data:www-data /var/www/vendor \
    && chmod -R 755 /var/www/vendor

# Expose Railway port
EXPOSE 8080

# Change Apache port from 80 -> 8080
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Allow .htaccess override for SPA routing
RUN echo '<Directory "/var/www/html">\nAllowOverride All\n</Directory>' \
    > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Set up Apache alias for API folder
RUN echo 'Alias /api /var/www/api\n<Directory "/var/www/api">\n    AllowOverride None\n    Require all granted\n</Directory>' \
    > /etc/apache2/conf-available/api.conf \
    && a2enconf api

# PHP Upload Settings and production error settings
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time = 240" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "display_errors = Off" > /usr/local/etc/php/conf.d/prod.ini \
 && echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/prod.ini \
 && echo "log_errors = On" >> /usr/local/etc/php/conf.d/prod.ini \
 && echo "error_log = /var/www/logs/php_errors.log" >> /usr/local/etc/php/conf.d/prod.ini

# Start Apache
CMD ["apache2-foreground"]
