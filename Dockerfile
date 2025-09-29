# Build stage for Composer dependencies
FROM composer:2.6-alpine AS composer-stage
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Production stage with Alpine Linux for smaller size
FROM php:8.1-fpm-alpine

# Install only essential system dependencies
RUN apk add --no-cache \
    nginx \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Create nginx user and directories
RUN addgroup -g 82 -S www-data \
    && adduser -u 82 -D -S -G www-data www-data \
    && mkdir -p /var/log/nginx /var/lib/nginx/tmp /run/nginx \
    && chown -R www-data:www-data /var/log/nginx /var/lib/nginx /run/nginx

# Set working directory
WORKDIR /var/www/html

# Copy application files (excluding vendor)
COPY --chown=www-data:www-data . .

# Copy Composer dependencies from build stage
COPY --from=composer-stage --chown=www-data:www-data /app/vendor ./vendor

# Copy optimized Nginx configuration
COPY --chown=www-data:www-data docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=www-data:www-data docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Remove default nginx config and create minimal php-fpm config
RUN rm -f /etc/nginx/http.d/default.conf.orig \
    && echo "user www-data;" > /etc/nginx/nginx.conf \
    && echo "worker_processes auto;" >> /etc/nginx/nginx.conf \
    && echo "error_log /var/log/nginx/error.log warn;" >> /etc/nginx/nginx.conf \
    && echo "pid /run/nginx.pid;" >> /etc/nginx/nginx.conf \
    && echo "events { worker_connections 1024; }" >> /etc/nginx/nginx.conf \
    && echo "http { include /etc/nginx/mime.types; include /etc/nginx/http.d/*.conf; }" >> /etc/nginx/nginx.conf

# Optimize PHP-FPM configuration
RUN echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf

# Set final permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \;

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start script
COPY --chown=www-data:www-data docker/start.sh /start.sh
RUN chmod +x /start.sh

# Switch to non-root user
USER www-data

CMD ["/start.sh"]
