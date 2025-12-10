# Dockerfile - PHP + Composer + ext required
FROM php:8.2-apache

# Install system deps and php extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip unzip git \
  && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite (optional)
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app
COPY . /var/www/html

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Ensure uploads dir exists and writable
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]
