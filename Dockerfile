FROM php:8.3-apache

# PostgreSQL (Supabase) via PDO.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost *:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

COPY BUSCA-AI/ /var/www/html/

# Mantém o esquema SQL fora da raiz pública e valida o ambiente no build.
RUN mkdir -p /var/lib/busca-ai \
    && if [ -f /var/www/html/busca_ai_novo.sql ]; then mv /var/www/html/busca_ai_novo.sql /var/lib/busca-ai/schema-mysql.sql; fi \
    && php -m | grep -qi '^pdo_pgsql$' \
    && find /var/www/html -type f -name '*.php' -print0 | xargs -0 -n1 php -l \
    && chown -R www-data:www-data /var/www/html /var/lib/busca-ai

EXPOSE 10000

CMD ["apache2-foreground"]
