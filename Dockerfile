FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost *:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

COPY BUSCA-AI/ /var/www/html/

# O esquema SQL fica fora da raiz pública para não ser baixável pelo navegador.
RUN mkdir -p /var/lib/busca-ai \
    && mv /var/www/html/busca_ai_novo.sql /var/lib/busca-ai/schema.sql \
    && chown -R www-data:www-data /var/www/html /var/lib/busca-ai

EXPOSE 10000

CMD ["apache2-foreground"]
