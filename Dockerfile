FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git libzip-dev unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libsqlite3-dev libonig-dev libcurl4-openssl-dev libxml2-dev \
    ca-certificates curl gnupg \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql mbstring zip xml gd opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN install -m 0755 -d /etc/apt/keyrings && \
    curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg && \
    chmod a+r /etc/apt/keyrings/docker.gpg && \
    echo "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
    "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" > /etc/apt/sources.list.d/docker.list && \
    apt-get update && \
    apt-get install -y docker-compose-plugin && \
    ln -s /usr/libexec/docker/cli-plugins/docker-compose /usr/local/bin/docker-compose && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -g 111 docker && usermod -aG docker www-data

COPY . /var/www/html/
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
