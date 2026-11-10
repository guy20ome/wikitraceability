FROM mediawiki:1.41

# Install dependencies
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
RUN echo "Installing SMW dependencies..." && \
    composer install --no-dev --ignore-platform-reqs --no-scripts 2>&1 || \
    (echo "Composer install failed, trying minimal..." && \
     composer install --no-dev --ignore-platform-reqs --no-scripts --prefer-lowest 2>&1) || \
    (echo "WARNING: SMW composer install failed, continuing anyway..." && \
     echo "SMW may work without composer dependencies")

WORKDIR /var/www/html

# Create WikiTraceability extension directory
RUN mkdir -p /var/www/html/extensions/WikiTraceability && \
    mkdir -p /var/www/html/extensions/WikiTraceability/templates

# Set proper permissions for extension directory
RUN chown -R www-data:www-data /var/www/html/extensions/WikiTraceability

# Create entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
# Wait for database to be ready\n\
if [ -n "$MEDIAWIKI_DB_HOST" ]; then\n\
  echo "Waiting for database..."\n\
  until mysql -h "$MEDIAWIKI_DB_HOST" -u "$MEDIAWIKI_DB_USER" -p"$MEDIAWIKI_DB_PASSWORD" "$MEDIAWIKI_DB_NAME" -e "SELECT 1;" 2>/dev/null; do\n\
    sleep 2\n\
  done\n\
  echo "Database is ready!"\n\
fi\n\
# Run database updates if needed\n\
if [ -f /var/www/html/LocalSettings.php ]; then\n\
  echo "Running MediaWiki updates..."\n\
  php /var/www/html/maintenance/update.php --quick 2>/dev/null || true\n\
fi\n\
# Ensure WikiTraceability extension is loaded\n\
if [ -f /var/www/html/LocalSettings.php ]; then\n\
  if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then\n\
    echo "Enabling WikiTraceability extension..."\n\
    echo "wfLoadExtension( \'WikiTraceability\' );" >> /var/www/html/LocalSettings.php\n\
  fi\n\
fi\n\
# Start Apache\n\
echo "Starting Apache..."\n\
exec apache2-foreground\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
