FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git libzip-dev unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql mbstring zip curl xml gd opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html/
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

WORKDIR /var/www/html
EXPOSE 80

CMD ["apache2-foreground"]