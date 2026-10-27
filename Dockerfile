FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite and headers
RUN a2enmod rewrite headers

# Support dynamic port binding (Render, Railway, Heroku pass $PORT)
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Setup permissions and required writeable directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/img/profiles /var/www/html/img/docs /var/www/html/outbound_emails \
    && chmod -R 777 /var/www/html/img/profiles /var/www/html/img/docs /var/www/html/outbound_emails

EXPOSE 80

CMD ["apache2-foreground"]
