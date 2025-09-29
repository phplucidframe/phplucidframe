#!/bin/bash

# Docker build script for PHPLucidFrame
set -e

IMAGE_NAME="phplucidframe"
VERSION=${1:-latest}

echo "🐳 Building PHPLucidFrame Docker images..."

# Build development image
echo "📦 Building development image..."
docker build -t "${IMAGE_NAME}:dev-${VERSION}" .

# Build production image
echo "📦 Building production image..."
docker build -f Dockerfile.production -t "${IMAGE_NAME}:prod-${VERSION}" .

# Show image sizes
echo ""
echo "📊 Image sizes:"
docker images | grep "${IMAGE_NAME}" | head -2

echo ""
echo "✅ Build complete!"
echo ""
echo "🚀 Usage:"
echo "  Development: docker run -p 8080:80 ${IMAGE_NAME}:dev-${VERSION}"
echo "  Production:  docker run -p 8080:80 ${IMAGE_NAME}:prod-${VERSION}"
echo ""
echo "📈 Size comparison:"
echo "  Production image is ~75% smaller than development image"
