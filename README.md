What is LucidFrame
============
PHPLucidFrame (a.k.a LucidFrame) is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development.
It uses a functional architecture to simplify complex application development. LucidFrame is especially designed for PHP, MySQL and Apache. It is simple, fast and easy to install.

Almost zero configuration - just configure your database setting and you are ready to go. No complex JSON, XML, YAML or vHost configuration.

No template engine to eliminate overhead of template processing and to save your storage from template cache files.

Although it is stated as mini framework, it supports a wide range of web application development features: 

- Datatase access API  
- Security control  
- URL routing  
- Validation helpers  
- Internationalization & Localization  
- User authentication API  
- Ajax  

Prerequisites
------
- Web Server (For example, Apache with mod_rewrite enabled)
- PHP version 5.1.6 or newer (mcrypt extension enabled, but by no means required.)
- MySQL 5.0+ with MySQLi enabled.
- jQuery (LucidFrame provides AJAX Form and List APIs which require [jQuery](http://jquery.com/), but the release contains jQuery 1.7.1)

Installation
------
Create a folder "LucidFrame" in your local webserver document root and extract the downloaded archive into the folder.
Then, check `http://localhost/LucidFrame` in your browser.

You could have a folder whatever name you like or even virtual host in your local development environment.
Just change the configuration variable `$lc_baseURL` in `\inc\config.php` in accordance with your folder name or virtual host name.

In your production environment, you may not have a folder and you could upload the framework files directly to your web server document root.
In this case, you have to set and leave `$lc_baseURL` empty.

By default, LucidFrame has home page routing which is defined as `$lc_homeRouting` in `\inc\config.php`. It maps to `\app\home\index.php`. You could have `home.php` or `welcome.php` or whatever you like. However, LucidFrame encourages a structured page organization. You can check the sample page folders and codes in the application folder`\app\` of the release.

You can also configure the other settings in `\inc\config.php` and `\inc\site.config.php` according to your requirement.

Documentation
------
[The complete PDF documentation](https://github.com/cithukyaw/LucidFrame/releases) can be downloaded in the release page.  
The quick reference and coding samples are also available in the release.

To run and check the sample administration panel, get `sample_db.sql` in [the release](https://github.com/cithukyaw/LucidFrame/releases) and import it. Then configure your database in `\inc\config.php`. It is accessible through http://www.example.com/admin or http://localhost/LucidFrame/admin. The default sample user is username: `admin` and password: `password`. The passwords are encrypted with the default security salt. If you change the salt, you will not be able to access the sample admin panel.