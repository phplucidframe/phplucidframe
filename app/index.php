<?php
/**
 * PHP 5
 *
 * LucidFrame : Simple & Flexible PHP Development
 * Copyright (c), LucidFrame.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @package     LC.app 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
/*
 * Request Collector
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
	$_page = route_search();	
	if($_page){
		include $_page;		
	}else{
		include( _i('inc/404.php') );
	}
}

ob_end_flush();