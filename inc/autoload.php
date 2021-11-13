<?php
/**
 * This file is used to load third-party packages/libraries globally
 * It is useful when you don't use composer and install third-party packages in the /third-party directory manually
 * You may not use this configuration if you use composer
 * @see https://phplucidframe.readthedocs.io/en/latest/auto-loading-libraries.html#custom-autoloader
 */

// Syntax to load a library
// _loader('library_file_name_without_extension',  THIRD_PARTY . 'library_folder_to_library_name');

// Syntax to autoload directory for a particular sub-site
// _autoloadDir(APP_ROOT . 'admin/middleware', 'admin');
