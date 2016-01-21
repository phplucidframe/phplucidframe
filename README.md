# What is PHPLucidFrame?

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/cithukyaw/LucidFrame?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

PHPLucidFrame (a.k.a LucidFrame) is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development.
It uses a functional architecture to simplify complex application development. PHPLucidFrame is especially designed for PHP, MySQL and Apache. It is simple, fast, lightweight and easy to install.

Almost zero configuration - just configure your database setting and you are ready to go. No complex JSON, XML, YAML or vHost configuration.

No template engine to eliminate overhead of template processing and to save your storage from template cache files.

Although it is stated as mini framework, it supports a wide range of web application development features:

- Datatase access API
- Security control
- URL routing
- Validation helpers
- Internationalization & Localization
- User authentication & authorization API
- Shell & Console
- Ajax

## Prerequisites

- Web Server (Apache with `mod_rewrite` enabled)
- PHP version 5.3.0 or newer (optional `mcrypt` extension enabled, but recommended)
- MySQL 5.0 or newer with MySQLi enabled.

## Installation

- Extract [the downloaded archive](http://phplucidframe.sithukyaw.com/download/release/latest) in your local webserver document root, and you will get a folder named **phplucidframe-x.y.z** where **x.y.z** would be your downloaded version.
- Rename it as **phplucidframe**.
- Open your terminal or command line and CD to your project root, and then run `php lucidframe secret:generate`. For more about the PHPLucidFrame console, read [the documentation section 16](http://phplucidframe.sithukyaw.com/cookbook).
- Check `http://localhost/phplucidframe` in your browser.
- (Optional, but recommended) Copy `/inc/tpl/head.php` to `/app/inc/tpl/head.php` if you want to update it.

**Note:** If you have your own project folder name other than **phplucidframe** in your development environment, you have to change the value of `baseURL` in `/inc/parameter/development.php` in accordance with your project name.

## Furthermore on Installation

**URL Rewrite** : Make sure you have `mod_rewrite` activated on your server / in your environment.
Some guidelines:

- [XAMPP for Windows](http://www.leonardaustin.com/blog/technical/enable-mod_rewrite-in-xampp/)
- [Ubuntu 14.04 LTS](http://www.dev-metal.com/enable-mod_rewrite-ubuntu-14-04-lts/)
- [Ubuntu 12.04 LTS](http://www.dev-metal.com/enable-mod_rewrite-ubuntu-12-04-lts/)
- [EasyPHP on Windows](http://stackoverflow.com/questions/8158770/easyphp-and-htaccess)
- [AMPPS on Windows/Mac OS](http://www.softaculous.com/board/index.php?tid=3634&title=AMPPS_rewrite_enable/disable_option%3F_please%3F)
- [MAMP on Mac OS](http://stackoverflow.com/questions/7670561/how-to-get-htaccess-to-work-on-mamp)

**Based URL** : There are two situations you will have to leave the configuration `baseURL` empty in `/inc/parameter/xxx.php` files:

1. when you have a virtual host for your application in your development environment.
2. when your application in production evironment where you upload the framework files directly to your web server document root.

**Routing** : You can define custom routes in `/inc/route.config.php`. The following routing for home page maps to `/app/home/index.php`.

    route('lc_home')->map('/', '/home');

PHPLucidFrame encourages a structured page organization. You can check the recommended structure in the sample page folders and codes `/app/home/` and `/app/example/` of the release.

**Additional Site Settings** : You can also configure the other settings in `/inc/config.php` and `/inc/site.config.php` or `/app/inc/site.config.php` according to your requirement.

**CSS Template** : PHPLucidFrame provides you a default site CSS template. The file is `/css/base.css`. No matter you want to use it or not, you may do one of the following two ways to make your site easily upgradable in the future:

 1. create your own file in `/css` or `/app/css` with whatever name you like and update your `/inc/tpl/head.php` or `/app/inc/tpl/head.php` by including your file. Then you can override the rules of `/css/base.css` or write your own rules in your file.
 2. copy `/css/base.css` to `/app/css/base.css` and update it to your need. Then you don't have to update `head.php`.

See more in [PDF documentation](http://phplucidframe.sithukyaw.com/cookbook)

## Demo with Sample Administration Module

- To run and check the sample administration module, have a look at [the configuration guideline](https://github.com/phplucidframe/phplucidframe/wiki/Configuration-for-The-Sample-Administration-Module).
- This Github project are successfully running at [demo-phplucidframe.rhcloud.com](http://demo-phplucidframe.rhcloud.com) and [demo-phplucidframe.rhcloud.com/admin](http://demo-phplucidframe.rhcloud.com/admin) with default user *admin/password*.

## Documentation

- [PDF Documentation](http://phplucidframe.sithukyaw.com/cookbook) - The complete PDF documentation is available to download.
- [API Documentation](http://phplucidframe.sithukyaw.com/api) - API documentation of every version is available and generated by [phpDocumentor](http://phpdoc.org).
- [Code Samples](https://github.com/cithukyaw/LucidFrame/releases/latest) - The quick reference and coding samples are also available in the release.

## Support & Resources

- [Community Forum](http://phplucidframe.sithukyaw.com/community)
- [Stackoverflow](http://stackoverflow.com/questions/tagged/phplucidframe)
- [GitHub issues](https://github.com/cithukyaw/LucidFrame/issues)
- [Gitter IM](http://gitter.im/cithukyaw/LucidFrame)
- [Roadmap](https://trello.com/b/zj5l6GP1/phplucidframe-development)
