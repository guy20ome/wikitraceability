#!/bin/bash

# Docker MediaWiki Setup Helper Script

set -e

case "${1:-help}" in
  up)
    echo "Starting MediaWiki services..."
    docker-compose up -d
    echo "Waiting for services to be ready..."
    sleep 10
    echo "✓ Services started!"
    echo "MediaWiki is available at: http://localhost:8080/w"
    echo "Admin user: Admin / admin123"
    ;;
  
  down)
    echo "Stopping MediaWiki services..."
    docker-compose down
    echo "✓ Services stopped"
    ;;
  
  logs)
    docker-compose logs -f mediawiki
    ;;
  
  shell)
    echo "Entering MediaWiki container shell..."
    docker-compose exec mediawiki bash
    ;;
  
  restart)
    echo "Restarting MediaWiki..."
    docker-compose restart mediawiki
    echo "✓ MediaWiki restarted"
    ;;
  
  rebuild)
    echo "Rebuilding Docker image..."
    docker-compose down
    docker-compose build --no-cache
    docker-compose up -d
    echo "✓ Image rebuilt and services started"
    ;;
  
  clean)
    echo "WARNING: This will remove all containers, images, and volumes."
    read -p "Are you sure? (yes/no): " confirm
    if [ "$confirm" = "yes" ]; then
      docker-compose down -v
      docker system prune -f
      echo "✓ Cleanup complete"
    else
      echo "Cleanup cancelled"
    fi
    ;;
  
  status)
    docker-compose ps
    ;;
  
  *)
    echo "Docker MediaWiki Setup Helper"
    echo ""
    echo "Usage: $0 {command}"
    echo ""
    echo "Commands:"
    echo "  up         - Start all services"
    echo "  down       - Stop all services"
    echo "  logs       - View MediaWiki logs"
    echo "  shell      - Open shell in MediaWiki container"
    echo "  restart    - Restart MediaWiki service"
    echo "  rebuild    - Rebuild image and restart services"
    echo "  clean      - Remove all containers, images, and volumes"
    echo "  status     - Show service status"
    echo "  help       - Show this help message"
    ;;
esac
