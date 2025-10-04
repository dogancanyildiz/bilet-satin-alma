FROM php:8.2-apache

# SQLite (PDO) eklentileri
RUN docker-php-ext-install pdo pdo_sqlite

# mod_rewrite aktif
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