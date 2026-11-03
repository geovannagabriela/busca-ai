FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost *:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

COPY aiven-ca.pem /etc/ssl/certs/aiven-ca.pem
COPY BUSCA-AI/ /var/www/html/

RUN chmod 644 /etc/ssl/certs/aiven-ca.pem \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
