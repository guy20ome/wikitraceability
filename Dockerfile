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

# Clone and install Semantic MediaWiki (compatible with MW 1.41)
RUN git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/SemanticMediaWiki.git \
    /var/www/html/extensions/SemanticMediaWiki --depth 1 --branch REL1_41

WORKDIR /var/www/html/extensions/SemanticMediaWiki
RUN composer install --no-dev --prefer-dist

WORKDIR /var/www/html

# Create WikiTraceability extension directory
RUN mkdir -p /var/www/html/extensions/WikiTraceability

# Create templates directory
RUN mkdir -p /var/www/html/extensions/WikiTraceability/templates

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
fi\n\
# Run database updates if needed\n\
if [ -f /var/www/html/LocalSettings.php ]; then\n\
  php /var/www/html/maintenance/update.php --quick 2>/dev/null || true\n\
fi\n\
# Ensure WikiTraceability extension is loaded\n\
if [ -f /var/www/html/LocalSettings.php ]; then\n\
  if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then\n\
    echo "wfLoadExtension( \'WikiTraceability\' );" >> /var/www/html/LocalSettings.php\n\
  fi\n\
fi\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
