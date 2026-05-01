FROM php:8.2-apache

WORKDIR /var/www/html

# Install PHP extensions commonly needed by LMS projects.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" curl mysqli pdo pdo_mysql mbstring intl gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Use production PHP defaults and allow .htaccess rules.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri 's!/var/www/!/var/www/html!g' /etc/apache2/apache2.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy LMS source code (Docker build context is the existing TaleemX directory).
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
