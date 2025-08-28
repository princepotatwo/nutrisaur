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
COPY config.php ./config.php

# Create proper health check
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s")]); ?>' > public/health.php

# Ensure config.php is accessible from public directory
RUN cp config.php public/config.php

# Ensure sss directory is accessible from public directory
RUN ln -sf ../sss public/sss

# Create startup script that uses Railway's PORT
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'set -e' >> /start.sh && \
    echo 'echo "Environment variables:"' >> /start.sh && \
    echo 'env | grep -E "(PORT|RAILWAY)" || echo "No PORT or RAILWAY variables found"' >> /start.sh && \
    echo 'PORT=${PORT:-8000}' >> /start.sh && \
    echo 'echo "Using port: $PORT"' >> /start.sh && \
    echo 'echo "Starting PHP server on port $PORT"' >> /start.sh && \
    echo 'php -S 0.0.0.0:$PORT -t public' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 8000

# Start using the script
CMD ["/bin/bash", "/start.sh"]
