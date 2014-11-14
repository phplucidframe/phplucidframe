<?php
/**
 * PHPLucidFrame : Simple, Lightweight & yet Powerfull PHP Application Framework
 * The request collector
 *
 * @package		LC
 * @since		PHPLucidFrame v 1.0.0
 * @copyright	Copyright (c), PHPLucidFrame.
 * @author 		Sithu K. <hello@sithukyaw.com>
 * @link 		http://phplucidframe.sithukyaw.com
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

require_once '../inc/bootstrap.php';

ob_start('_flush');

$q = route_path();

# if the route is empty, get it from the config
if(empty($q) && $lc_homeRouting) $q = $lc_homeRouting;
# if it is still empty, set it to the system default
if(empty($q)) $q = 'home';
# Get the complete path to root
$_page = ROOT . $q;
if(!file_exists($_page)){
	# Get the complete path with app/
	$_page = APP_ROOT . $q;
}

# if it is a directory, it should have index.php
if(is_dir($_page)) $_page .= '/index.php';

if(!empty($_page) && file_exists($_page)){
	include $_page;
}else{
	if(preg_match('/(.*)(401|403|404){1}$/', $_page, $matches)){
		include( _i('inc/tpl/'.$matches[2].'.php') );
	}else{
		$_page = route_search();
		if($_page){
			include $_page;
		}else{
			include( _i('inc/tpl/404.php') );
		}
	}
}

ob_end_flush();
