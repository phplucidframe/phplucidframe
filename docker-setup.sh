#!/bin/bash

echo "🐳 Setting up PHPLucidFrame with Docker..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Copy environment file
echo "📝 Setting up environment..."
cp .lcenv.docker .lcenv

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p docker/nginx/logs

# Build and start containers
echo "🏗️  Building and starting containers..."
docker-compose up -d --build

# Wait for containers to be ready
echo "⏳ Waiting for containers to be ready..."
sleep 10

# Generate security secret
echo "🔐 Generating security secret..."
docker-compose exec -T web php lucidframe secret:generate

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Access your application:"
echo "   Main App: http://localhost:8080"
echo "   phpMyAdmin: http://localhost:8081"
echo ""
echo "📊 Database credentials:"
echo "   Host: localhost:3306"
echo "   Database: lucidframe"
echo "   Username: lucidframe"
echo "   Password: lucidframe_password"
echo ""
echo "🔧 Useful commands:"
echo "   View logs: docker-compose logs web"
echo "   Stop: docker-compose down"
echo "   Restart: docker-compose restart"
echo ""
