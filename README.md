# What is PHPLucidFrame?

PHPLucidFrame (a.k.a. LucidFrame) is an application development framework for PHP developers. It provides logical structure and several helper utilities for web application development. It uses a functional architecture to simplify complex application development. It is especially designed for PHP, MySQL and Apache. It is simple, fast, lightweight and easy to install.

Almost zero configuration - just configure your database setting and you are ready to go. No complex JSON, XML, YAML or vHost configuration.

No template engine to eliminate overhead of template processing and to save your storage from template cache files.

It supports a wide range of web application development features:

- Database access API
- Security control
- URL routing
- Validation helpers
- Internationalization & Localization
- User authentication & authorization API
- Schema Manager
- Database Seeding
- Query Builder
- Shell & Console Tool
- Ajax

<table>
    <body>
        <tr>
            <td><img src="https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.png" width="50" alt="JetBrains Logo"></td>
            <td>A big thank you to <a href="https://www.jetbrains.com">JetBrains</a> for supporting this project with free open-source licences of their IDEs.</td>
        </tr>
    </body>
</table>

## Prerequisites

- Web Server (Apache with `mod_rewrite` enabled or Nginx)
- PHP version 7.1 or newer is recommended, but we strongly advise you to use one of the currently supported versions.
- MySQL 5.0 or newer

## Docker Installation (Recommended)

The easiest way to get PHPLucidFrame running is using Docker with PHP, MySQL, and Nginx:

### Prerequisites for Docker
- Docker and Docker Compose installed on your system

### Quick Start with Docker

1. Clone or download the PHPLucidFrame repository
2. Navigate to the project directory
3. Run the following commands:

```bash
# Copy the Docker environment file
cp .lcenv.docker .lcenv

# Development (with volumes for live editing)
docker-compose up -d

# Production (optimized, smaller image)
docker-compose -f docker-compose.prod.yml up -d

# Generate security secret (run this once)
docker-compose exec web php lucidframe secret:generate
```

### Docker Image Optimization

We provide two Docker configurations:

- **Development** (`Dockerfile`): ~200MB, includes development tools, live file mounting
- **Production** (`Dockerfile.production`): ~80MB, Alpine-based, optimized for production

**Image size comparison:**
- Standard PHP-FPM + Nginx: ~400MB
- Our optimized image: ~80MB (80% smaller!)

**Optimizations included:**
- Multi-stage builds to exclude build dependencies
- Alpine Linux base for minimal footprint
- Optimized PHP-FPM and Nginx configurations
- Removed unnecessary packages and files
- Production-ready security settings

4. Access your application:
   - **Main Application**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081 (username: `lucidframe`, password: `lucidframe_password`)

### Docker Services

The Docker setup includes:
- **Web Server**: Nginx with PHP 8.1-FPM
- **Database**: MySQL 8.0 with sample data
- **Database Management**: phpMyAdmin for easy database administration

### Docker Configuration

- **Application Port**: 8080
- **Database Port**: 3306 (accessible from host)
- **phpMyAdmin Port**: 8081
- **Database Name**: `lucidframe` (main), `lucid_blog` (sample)
- **Database User**: `lucidframe`
- **Database Password**: `lucidframe_password`

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs web
docker-compose logs db

# Access web container shell
docker-compose exec web bash

# Run LucidFrame console commands
docker-compose exec web php lucidframe [command]

# Rebuild containers (after code changes)
docker-compose up -d --build
```

## Manual Installation

- Extract [the downloaded archive](http://www.phplucidframe.com/download/release/latest) in your local webserver document root, and you will get a folder named **phplucidframe-x.y.z** where **x.y.z** would be your downloaded version.
- Rename it as **phplucidframe**.
- Open your terminal or command line and CD to your project root, and then run `php lucidframe secret:generate`. For more about the PHPLucidFrame console, read [the documentation section "The LucidFrame Console"](http://www.phplucidframe.com/download/doc/latest).
- Check `http://localhost/phplucidframe` in your browser.

**Note:**
- If you have your own project folder name other than `phplucidframe` in your development environment, you have to change the value of `baseURL` in `/inc/parameter/development.php` in accordance with your project name.
- If you use a virtual host for your project, you have to leave an empty string for the value of `baseURL` in `/inc/parameter/development.php`.

## Alternate Installation with Composer

You can install PHPLucidFrame alternatively using [Composer](http://getcomposer.org). Open your terminal and CD to your webserver document root, and then run

    composer create-project --prefer-dist phplucidframe/phplucidframe [your-project-name]

**Note:** You have to change the value of baseURL in `/inc/parameter/development.php` according to `[your-project-name]`.

## Furthermore on Installation

**Based URL** : There are two situations you will have to leave the configuration `baseURL` empty in `/inc/parameter/xxx.php` files:

1. when you have a virtual host for your application in your development environment.
2. when your application in production environment where you upload the framework files directly to your web server document root.

**Routing** : You can define custom routes in `/inc/route.config.php`. The following routing for home page maps to `/app/home/index.php`.

    route('lc_home')->map('/', '/home');

PHPLucidFrame encourages a structured page organization. You can check the recommended structure in the sample page folders and codes `/app/home/` and `/app/example/` of the release.

**Additional Site Settings** : You can also configure the other settings in `/inc/config.php` and `/app/inc/site.config.php` according to your requirement.

**CSS Template** : PHPLucidFrame provides you a default site CSS template `/assets/css/base.css`. To make your site easily upgradable in the future, create your own file in `/app/assets/css` with whatever name you like and update your `/app/inc/tpl/layout.php` by including `<?php _css('yourfilename.css'); ?>`. Then you can override the rules of `/assets/css/base.css` in your CSS file.

## Documentation

- [PDF Documentation](http://www.phplucidframe.com/download/doc/latest/pdf) - The complete PDF documentation is available to download.
- [API Documentation](http://www.phplucidframe.com#api) - API documentation of every version is available and generated by [ApiGen](http://apigen.org) and [phpDocumentor](http://phpdoc.org).
- [Code Samples](https://github.com/phplucidframe/phplucidframe/releases/latest) - The quick reference and coding samples are also available in the release.
- [Sample Administration Module](https://github.com/phplucidframe/phplucidframe/wiki/Configuration-for-The-Sample-Administration-Module) - The configuration guideline for sample administration module.

## Support & Resources

- [Stackoverflow](http://stackoverflow.com/questions/tagged/phplucidframe)
- [GitHub issues](https://github.com/phplucidframe/phplucidframe/issues)

## Docker Troubleshooting

### Common Issues

**Port conflicts**: If ports 8080, 3306, or 8081 are already in use, modify the ports in `docker-compose.yml`:
```yaml
ports:
  - "8090:80"  # Change 8080 to 8090
```

**Permission issues**: If you encounter permission errors:
```bash
# Fix file permissions
docker-compose exec web chown -R www-data:www-data /var/www/html
docker-compose exec web chmod -R 755 /var/www/html
```

**Database connection issues**: Ensure the database container is running:
```bash
docker-compose ps
docker-compose logs db
```

**Clear everything and start fresh**:
```bash
docker-compose down -v
docker-compose up -d --build
```

## Run Tests

Prerequisites:

    composer install

    php lucidframe env test

Create a test database and setup in `inc/parameter/test.php`. By default, the database name `lucid_blog_test` is set up under `sample` namespace. Then you can create a new database `lucid_blog_test` and run `schema:load sample`.

    php lucidframe schema:load sample

From **Command Line**,

    # to run all tests
    php tests/tests.php

    # to run tests/lib/db_helper.test.php only
    php tests/tests.php --file=db_helper

    # to run tests/lib/validation_helper.test.php only
    php tests/tests.php -f=validation_helper
    # or
    php tests/tests.php -f validation_helper

    # to run tests/lib/db_helper.test.php and query_builer.test.php
    php tests/tests.php --file=db_helper,query_builder

Note: You can also use the short-form option name `f` instead of `file`.

From **Browser**,

    # to run all tests
    http://[site_url]/tests/tests.php

    # to run tests/lib/db_helper.test.php only
    http://[site_url]/tests/tests.php?file=db_helper

    # to run tests/lib/db_helper.test.php and query_builer.test.php
    http://[site_url]/tests/tests.php?file=db_helper,query_builder

Note: You can also use the query string parameter `f` instead of `file`.
