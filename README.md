What is LucidFrame
============
LucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.

LucidFrame is simple, fast and easy to install. The minimum requirements are a web server and a copy of LucidFrame.

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
The complete PDF documentation is on the way, but the quick reference and coding samples are available in the release.