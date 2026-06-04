# Docker MediaWiki Setup Guide

This setup provides a local development environment for the WikiTraceability extension using Docker.

## Prerequisites

- Docker and Docker Compose installed
- At least 2GB of free disk space

## Quick Start

1. **Start the services:**
   ```bash
   docker-compose up -d
   ```

2. **Wait for initialization** (30-60 seconds):
   ```bash
   docker-compose logs -f mediawiki
   ```
   Wait for messages indicating the service is running.

3. **Access MediaWiki:**
   - Open your browser and go to: `http://localhost:8080/w`
   - Admin login: `Admin` / `admin123`

## Services

- **MediaWiki**: `http://localhost:8080/w` - Main wiki interface
- **MariaDB Database**: `database:3306` (internal, not exposed)

## Database Credentials

- Host: `database` (or `localhost` for external access)
- Database: `mediawiki`
- User: `mediawiki`
- Password: `mediawikipass`
- Root Password: `wikiroot`

## Common Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
docker-compose logs -f mediawiki
docker-compose logs -f database
```

### Restart Services
```bash
docker-compose restart
```

### Full Cleanup (removes data)
```bash
docker-compose down -v
```

### Access MediaWiki Container Shell
```bash
docker-compose exec mediawiki bash
```

## Development Workflow

1. Make changes to your extension files (they're mounted as volumes)
2. Refresh your browser to see changes
3. For PHP changes, you may need to restart the container:
   ```bash
   docker-compose restart mediawiki
   ```

## Extension Loading

The WikiTraceability extension is automatically mounted into the MediaWiki extensions directory and loaded via the `extension.json` configuration. Any changes to the extension files are immediately reflected in the running container.

## Troubleshooting

### Container won't start
```bash
docker-compose down
docker-compose up --build
```

### Database connection error
```bash
docker-compose down -v
docker-compose up -d
```
This recreates the database with fresh initialization.

### Permission issues
```bash
docker-compose exec mediawiki chown -R www-data:www-data /var/www/html/w/extensions/WikiTraceability
```

### View MediaWiki error logs
```bash
docker-compose exec mediawiki tail -f /var/www/html/w/debug.log
```

## Useful Links

- [MediaWiki Official Documentation](https://www.mediawiki.org)
- [MediaWiki API Documentation](https://www.mediawiki.org/wiki/API)
- [Extension Development Guide](https://www.mediawiki.org/wiki/Extension_development)
