# ============================
# Nsikacart Dockerfile (Railway)
# ============================

# Use official PHP with Apache
FROM php:8.2-apache

# install system packages required for curl and other libs
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    zip \
    unzip \
    git \
    && docker-php-ext-install mysqli pdo pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for SPA routing
RUN a2enmod rewrite

# Set working directory to public folder
WORKDIR /var/www/html

# ============================
# Copy frontend
# ============================
COPY public/ /var/www/html/
COPY auth/ /var/www/html/auth/

# ============================
# Copy backend & helpers (API outside public)
# ============================
COPY api/ /var/www/api/
COPY helpers/ /var/www/helpers/
COPY logs/ /var/www/logs/

# ensure composer/vendor are available inside image
COPY vendor/ /var/www/vendor/

# ============================
# Fix logs folder permissions
# ============================
RUN mkdir -p /var/www/logs \
    && chown -R www-data:www-data /var/www/logs \
    && chmod -R 775 /var/www/logs

# ============================
# Expose Railway port
# ============================
EXPOSE 8080

# ============================
# Change Apache port from 80 -> 8080
# ============================
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# ============================
# Allow .htaccess override for SPA routing
# ============================
RUN echo '<Directory "/var/www/html">\nAllowOverride All\n</Directory>' \
    > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# ============================
# Set up Apache alias for API folder
# ============================
RUN echo 'Alias /api /var/www/api\n<Directory "/var/www/api">\n    AllowOverride None\n    Require all granted\n</Directory>' \
    > /etc/apache2/conf-available/api.conf \
    && a2enconf api

# ============================
# PHP Upload Settings
# ============================
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini

# ============================
# Start Apache
# ============================
CMD ["apache2-foreground"]
