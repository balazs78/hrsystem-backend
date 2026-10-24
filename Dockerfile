# Using the official PHP 8.2 CLI image as the base image
FROM php:8.2-cli

# Install necessary dependencies for SQLite, Composer, and Image Processing (GD)
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo_sqlite \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy the application code into the container
COPY . .

# Install dependencies for production optimized for performance
RUN composer install --no-dev --optimize-autoloader

# Prepare the SQLite database file
RUN touch database/database.sqlite

# Set the environment variable for the Render port (default is 10000)
ENV PORT=10000

# Startup order:
# 1. Force database migration and seeding
# 2. Cache the framework for optimal performance
# 3. Start the Laravel built-in server on the dynamic port assigned by Render
CMD php artisan migrate --seed --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=$PORT
