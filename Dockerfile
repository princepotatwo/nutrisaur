FROM php:8.1-cli

# Install only essential dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy files
COPY sss/ ./sss/
COPY public/ ./public/

# Create simple health check
RUN echo '<?php echo "OK"; ?>' > public/health.php

# Create simple index
RUN echo '<?php echo "Nutrisaur is running!"; ?>' > public/index.php

# Expose port
EXPOSE 8000

# Start PHP server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
