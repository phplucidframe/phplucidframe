# What is LucidFrame?

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
- User authentication & authorization API
- Ajax

## Prerequisites

- Web Server (For example, Apache with mod_rewrite enabled)
- PHP version 5.2.0 or newer (mcrypt extension enabled, but by no means required.)
- MySQL 5.0+ with MySQLi enabled.

## Installation

Extract [the downloaded archive](https://github.com/cithukyaw/LucidFrame/releases/latest) in your local webserver document root, and you will get a folder named **LucidFrame-x.y.z**. The **x.y.z** would be your downloaded version. Rename it as **LucidFrame** and then check `http://localhost/LucidFrame` in your browser. That's it!

You could have a folder whatever name you like or even virtual host in your development environment.
Just change the configuration variable `$lc_baseURL` in `\inc\config.php` in accordance with your folder name or virtual host name.

In your production environment, you may not have a folder and you could upload the framework files directly to your web server document root.
In this case, you have to set and leave `$lc_baseURL` empty.

By default, LucidFrame has home page routing which is defined as `$lc_homeRouting` in `\inc\config.php`. It maps to `\app\home\index.php`. You could have `home.php` or `welcome.php` or whatever you like. However, LucidFrame encourages a structured page organization. You can check the sample page folders and codes in the application folder`\app\` of the release.

You can also configure the other settings in `\inc\config.php` and `\inc\site.config.php` according to your requirement.

## Documentation

The complete PDF documentation can be downloaded [here](http://phplucidframe.sithukyaw.com/cookbook). The quick reference and coding samples are also available in the release.

To run and check the sample administration module, check [the configuration](https://github.com/cithukyaw/LucidFrame/wiki/Configuration-for-The-Sample-Administration-Module).

## Communication & Get Support!

[Community Forum](http://phplucidframe.sithukyaw.com/community) - Community mailing list and forum

[IRC on channel](http://webchat.freenode.net/?channels=#phplucidframe) `#phplucidframe` - You're welcome to drop in and ask questions, discuss bugs and such. The channel is not currently logged.

[GitHub issues](https://github.com/cithukyaw/LucidFrame/issues) - The primary way for communicating about specific proposed changes and issues to this project.

[Roadmap](https://trello.com/b/zj5l6GP1/phplucidframe-development) - The road of LucidFrame development.
