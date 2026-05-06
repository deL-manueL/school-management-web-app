FROM php:8.2-apache
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && apachectl configtest 2>&1 | grep -q "Syntax OK"

# Extensions — mysqli for the app, pdo_mysql as a reliable fallback driver
RUN docker-php-ext-install mysqli pdo pdo_mysql

# mod_rewrite is required for admin-dashboard/.htaccess (RewriteEngine directives).
# This is NOT an MPM module; enabling it does not affect the MPM selection.
RUN a2enmod rewrite

# Allow .htaccess files to override Apache defaults inside the document root.
# Without AllowOverride All, every .htaccess directive is silently ignored.
RUN printf '<Directory /var/www/html>\n\
\tOptions -Indexes +FollowSymLinks\n\
\tAllowOverride All\n\
\tRequire all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/override-docroot.conf \
    && a2enconf override-docroot

# Entrypoint rewires Apache to listen on Railway's injected $PORT (default 80).
# Copied before COPY . so it does not end up in the document root.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html
COPY . /var/www/html/
RUN rm -f /var/www/html/docker-entrypoint.sh

# Session directory must exist and be writable by the Apache process user.
RUN mkdir -p /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
