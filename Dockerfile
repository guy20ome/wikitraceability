FROM mediawiki:1.41

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    mariadb-client \
    vim \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

RUN git clone https://github.com/SemanticMediaWiki/SemanticMediaWiki.git \
    /var/www/html/extensions/SemanticMediaWiki --branch 5.1.0

WORKDIR /var/www/html/extensions/SemanticMediaWiki

RUN composer install --no-dev

WORKDIR /var/www/html

COPY ./includes/DatabaseMySQL.php /var/www/html/includes/libs/rdbms/database/DatabaseMySQL.php
#COPY ./LocalSettings1.php /var/www/html/LocalSettings.php
#RUN php ./maintenance/update.php

# Set proper permissions
#RUN chown -R www-data:www-data /var/www/html/extensions/SemanticMediaWiki
#RUN chown -R www-data:www-data /var/www/html/extensions/WikiTraceability

# Create entrypoint script to enable extension after setup
RUN echo '#!/bin/bash\n\
set -e\n\
# Enable the extension if LocalSettings.php exists and is properly configured\n\
if [ -f /var/www/html/LocalSettings.php ] && grep -q "wgDBuser" /var/www/html/LocalSettings.php; then\n\
  if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then\n\
    echo "Enabling WikiTraceability extension..."\n\
    echo '\''wfLoadExtension( '\''WikiTraceability'\'');'\'' >> /var/www/html/LocalSettings.php\n\
  fi\n\
fi\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
