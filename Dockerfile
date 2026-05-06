FROM php:8.2-apache

# ── PHP extensions ──────────────────────────────────────────────────────────
RUN docker-php-ext-install mysqli

# ── Apache modules ───────────────────────────────────────────────────────────
# mod_rewrite  — required for admin-dashboard/.htaccess RewriteEngine directives
# mod_headers  — enables CORS and cache-control headers from PHP
RUN a2enmod rewrite headers

# ── Document-root policy ─────────────────────────────────────────────────────
# Allow .htaccess files to override defaults (Options, AuthConfig, FileInfo, Limit).
# Written as a separate conf so it cleanly overrides the default AllowOverride None
# block in apache2.conf without a fragile sed on a system file.
RUN printf '<Directory /var/www/html>\n\
\tOptions -Indexes +FollowSymLinks\n\
\tAllowOverride All\n\
\tRequire all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/override-docroot.conf \
    && a2enconf override-docroot

# ── Entrypoint ───────────────────────────────────────────────────────────────
# Must be copied before the project COPY so it lives in /usr/local/bin only —
# it is removed from the web root after the project copy below.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ── Project files ────────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . /var/www/html/

# Remove entrypoint from the document root (it was dragged in by COPY .).
RUN rm -f /var/www/html/docker-entrypoint.sh

# ── Session storage ──────────────────────────────────────────────────────────
# config.php writes sessions to storage/sessions/ (relative to project root).
# The directory must exist and be writable by the www-data (Apache) user.
RUN mkdir -p /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# ── Runtime ──────────────────────────────────────────────────────────────────
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
