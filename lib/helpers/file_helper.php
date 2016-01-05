<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package     LC\Helpers\File
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

use LucidFrame\File\File;
use LucidFrame\File\AsynFileUploader;

/**
 * Simple helper to create File object
 * @since   PHPLucidFrame v 1.11.0
 * @param   string $fileName (optinal) Path to the file
 * @return  File
 */
function file_fileHelper($fileName = '')
{
    return new File($fileName);
}

/**
 * Simple helper to create AsynFileUploader object
 * @since   PHPLucidFrame v 1.11.0
 * @param   string/array anonymous The input file name or The array of property/value pairs
 * @return  AsynFileUploader
 */
function file_asynFileUploader()
{
    if (func_num_args()) {
        return new AsynFileUploader(func_get_arg(0));
    } else {
        return new AsynFileUploader();
    }
}
