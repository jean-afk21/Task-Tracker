FROM php:8.2-apache

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Install PHP extensions required by the project
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy the entire project into Apache's web root
COPY . /var/www/html/

# Install Composer dependencies, including Resend
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80