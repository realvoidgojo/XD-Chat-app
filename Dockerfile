FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip

# Enable Apache modules
RUN a2enmod rewrite headers deflate expires

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create uploads directory and set proper permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod 644 /var/www/html/.htaccess

# Configure Apache
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set PHP configuration for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 2M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 2M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_file_uploads = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 128M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_execution_time = 30" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.enable = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.memory_consumption = 128" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.max_accelerated_files = 4000" >> "$PHP_INI_DIR/php.ini"

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]