<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for pagination
 *
 * @package     LC\Helpers\Pagination
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

require_once HELPER . 'classes' . _DS_ . 'Pager.php';

/**
 * Create and return pagination object
 * @param string $pageQueryStr The customized page query string name
 */
function pager($pageQueryStr = '')
{
    return new Pager($pageQueryStr);
}
