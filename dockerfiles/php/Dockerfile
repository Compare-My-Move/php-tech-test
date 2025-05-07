FROM php:8.1-apache

# Install necessary dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    unzip \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql

# Enable Apache modules (if needed)
RUN a2enmod rewrite headers

# Set the document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -i 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's!<Directory /var/www/>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/sites-available/000-default.conf

# Clear default Apache site
RUN rm -rf /var/www/html/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Generate self-signed SSL certificate
RUN mkdir /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/selfsigned.key \
    -out /etc/apache2/ssl/selfsigned.crt \
    -subj "/C=US/ST=Dev/L=Local/O=Dev/CN=localhost"

# Enable SSL module and default SSL site
RUN a2enmod ssl && \
    a2ensite default-ssl

# Replace default SSL certificate paths in the default-ssl config
RUN sed -i 's|SSLCertificateFile.*|SSLCertificateFile /etc/apache2/ssl/selfsigned.crt|' /etc/apache2/sites-available/default-ssl.conf && \
    sed -i 's|SSLCertificateKeyFile.*|SSLCertificateKeyFile /etc/apache2/ssl/selfsigned.key|' /etc/apache2/sites-available/default-ssl.conf

# # Copy your project files
# COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/public

# Expose port 80 (already default for php:apache)
EXPOSE 80

