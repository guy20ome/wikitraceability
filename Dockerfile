FROM mediawiki:1.41

# Install build and runtime tools for SMW
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    mariadb-client \
    vim \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Semantic MediaWiki using git clone from GitHub (most reliable)
# SMW 5.1.0 is compatible with MediaWiki 1.41
RUN echo "Cloning Semantic MediaWiki..." && \
    git clone --depth 1 --branch 5.1.0 https://github.com/SemanticMediaWiki/SemanticMediaWiki.git \
        /var/www/html/extensions/SemanticMediaWiki 2>&1 || \
    (echo "Failed with 5.1.0, trying 5.0.0..." && \
     git clone --depth 1 --branch 5.0.0 https://github.com/SemanticMediaWiki/SemanticMediaWiki.git \
        /var/www/html/extensions/SemanticMediaWiki 2>&1) || \
    (echo "Failed with 5.0.0, trying 4.1.0..." && \
     git clone --depth 1 --branch 4.1.0 https://github.com/SemanticMediaWiki/SemanticMediaWiki.git \
        /var/www/html/extensions/SemanticMediaWiki 2>&1) || \
    (echo "All git clones failed, trying without branch..." && \
     git clone --depth 1 https://github.com/SemanticMediaWiki/SemanticMediaWiki.git \
        /var/www/html/extensions/SemanticMediaWiki 2>&1) || \
    (echo "ERROR: Failed to clone Semantic MediaWiki!" && exit 1)

# Install SMW dependencies
WORKDIR /var/www/html/extensions/SemanticMediaWiki
# Dev dependencies are excluded from installation and audited separately.
RUN echo "=== Installing SMW dependencies ===" && \
    echo "PHP version:" && php -v && \
    echo "Installed PHP modules:" && php -m | tr ' ' '\n' && \
    echo "Running composer install..." && \
    composer install --no-dev --no-blocking --no-interaction 2>&1 && \
    composer audit --no-dev 2>&1 && \
    echo "=== SMW dependencies installed successfully ==="

WORKDIR /var/www/html

# Create WikiTraceability extension directory
RUN mkdir -p /var/www/html/extensions/WikiTraceability && \
    mkdir -p /var/www/html/extensions/WikiTraceability/templates

# Set proper permissions for extension directory
RUN chown -R www-data:www-data /var/www/html/extensions/WikiTraceability

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    echo "Entrypoint script copied and made executable" && \
    ls -la /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
