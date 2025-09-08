FROM php:8.1-cli

# Install only PHP dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && docker-php-ext-install pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create startup script
RUN echo 'php -S 0.0.0.0:${PORT:-8080} -t public' > /start.sh && \
    chmod +x /start.sh

EXPOSE 8080
CMD ["/bin/bash", "/start.sh"]
