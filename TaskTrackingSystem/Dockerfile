FROM php:8.2-apache

FROM php:8.2-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY . .

RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-dev --optimize-autoloader

RUN echo "DocumentRoot /var/www/html/TaskTrackingSystem" >> /etc/apache2/sites-enabled/000-default.conf && \
    echo "<Directory /var/www/html/TaskTrackingSystem>" >> /etc/apache2/sites-enabled/000-default.conf && \
    echo "    AllowOverride All" >> /etc/apache2/sites-enabled/000-default.conf && \
    echo "    Require all granted" >> /etc/apache2/sites-enabled/000-default.conf && \
    echo "</Directory>" >> /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80
