FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    nodejs \
    npm

RUN docker-php-ext-install pdo_mysql opcache mbstring zip gd intl

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Adjust permissions for the web server
RUN chown -R www-data:www-data /var/www/html

CMD ["php-fpm"]
