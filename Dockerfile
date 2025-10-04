FROM php:8.2-apache

# Gerekli bağımlılıkları yükle (sqlite3 ve derleme araçları)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# mod_rewrite aktif et
RUN a2enmod rewrite

# DocumentRoot'u /public yap ve .htaccess'e izin ver
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/default-ssl.conf \
 && printf '<Directory "/var/www/html/public">\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/z-override.conf \
 && a2enconf z-override

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 80