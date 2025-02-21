# Use official PHP image with CLI and required extensions
FROM php:8.3-cli

# Install SQLite and enable required extensions
RUN apt-get update && apt-get install -y \
    zip unzip git sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite


# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory inside the container
WORKDIR /var/www

# Copy project files into the container
COPY . .


# Install PHP dependencies using Composer
RUN composer install --no-dev --optimize-autoloader

# Expose port (optional, for future API server)
EXPOSE 8085

# Command to keep the container running
CMD ["php", "-S", "0.0.0.0:8085", "-t", "public"]
