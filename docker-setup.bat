@echo off
echo 🐳 Setting up PHPLucidFrame with Docker...

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker first.
    pause
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

REM Copy environment file
echo 📝 Setting up environment...
copy .lcenv.docker .lcenv

REM Create necessary directories
echo 📁 Creating directories...
if not exist "docker\nginx\logs" mkdir docker\nginx\logs

REM Build and start containers
echo 🏗️  Building and starting containers...
docker-compose up -d --build

REM Wait for containers to be ready
echo ⏳ Waiting for containers to be ready...
timeout /t 10 /nobreak >nul

REM Generate security secret
echo 🔐 Generating security secret...
docker-compose exec -T web php lucidframe secret:generate

echo.
echo ✅ Setup complete!
echo.
echo 🌐 Access your application:
echo    Main App: http://localhost:8080
echo    phpMyAdmin: http://localhost:8081
echo.
echo 📊 Database credentials:
echo    Host: localhost:3306
echo    Database: lucidframe
echo    Username: lucidframe
echo    Password: lucidframe_password
echo.
echo 🔧 Useful commands:
echo    View logs: docker-compose logs web
echo    Stop: docker-compose down
echo    Restart: docker-compose restart
echo.
pause
