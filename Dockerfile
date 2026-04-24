FROM php:8.3-apache

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN apt-get update && apt-get install -y \
    wget \
    gnupg2 \
    git \
    libzip-dev \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libonig-dev \
    supervisor \
    cron \
    sudo \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        mbstring \
        zip \
        curl \
        xml \
        gd \
        opcache \
    && a2enmod rewrite \
    && a2enmod headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y mariadb-server mariadb-client && apt-get clean

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/mariadb.cnf /etc/mysql/mariadb.conf.d/99-tornops.cnf
COPY docker/init-db.sh /usr/local/bin/init-db.sh
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/init-db.sh /usr/local/bin/entrypoint.sh

RUN mkdir -p /run/mysqld /var/lib/mysql \
    && chown mysql:mysql /run/mysqld /var/lib/mysql

RUN usermod -u 1000 www-data

RUN mkdir -p /var/www && chown -R www-data:www-data /var/www && chmod -R 777 /var/www

WORKDIR /var/www/html

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]