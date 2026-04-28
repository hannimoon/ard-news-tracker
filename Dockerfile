FROM php:8.2-fpm

# Build arguments
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG INSTALL_XDEBUG=false

# Install system dependencies
# Install system dependencies + $PHPIZE_DEPS für PECL
RUN apt-get update && apt-get install -y \
$PHPIZE_DEPS \
git \
curl \
libpng-dev \
libonig-dev \
libxml2-dev \
zip \
unzip \
libzip-dev \
libicu-dev \
&& rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
pdo_mysql \
mbstring \
exif \
pcntl \
bcmath \
gd \
zip \
intl

RUN if [ "$INSTALL_XDEBUG" = "true" ] ; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug  \
        && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    else \
        echo "Xdebug skip"; \
    fi

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user with same UID/GID as host
RUN groupadd -g ${GROUP_ID} appgroup && \
useradd -u ${USER_ID} -g appgroup -m appuser

# Set working directory
WORKDIR /var/www/html

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set permissions
RUN chown -R appuser:appgroup /var/www/html

# Use non-root user
USER appuser

# Expose port
EXPOSE 9000

# Default command
CMD ["php-fpm"]
