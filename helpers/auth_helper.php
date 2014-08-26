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
 * @package     LC.helpers 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
if(!function_exists('auth_create')){
/**
 * Create Authentication object
 * This function is overwritable from the custom helpers/auth_helper.php 
 *
 * @param  string $id PK value
 * @param  object $data	The user data object (optional). If it is not given, auth_create will load it from db
 *
 * @return object The authenticated user object or FALSE on failure
 */
	function auth_create($id, $data=NULL){
		global $lc_auth;

		if(!isset($lc_auth['table']) || !isset($lc_auth['fields'])){
			trigger_error('Define $lc_auth[table] and $lc_auth[fields] in the configuration file.', E_USER_NOTICE);
			return false;
		}
		
		$auth = auth_get();
		if(!$auth){			
			$table 		= db_prefix() . str_replace(db_prefix(), '', $lc_auth['table']);
			$fieldId	= $lc_auth['fields']['id'];
			$fieldRole 	= $lc_auth['fields']['role'];	
			
			if(is_object($data)){
				$session = $data;
			}else{			
				$sql = 'SELECT * FROM '.$table.' WHERE '.$fieldId.' = :id LIMIT 1';
				if($result = db_query($sql, array(':id'=>$id))){
					$session = db_fetchObject($result);
				}
			}
			if(isset($session)){
				$session->sessId 		= session_id();
				$session->timestamp 	= md5(time());
				$session->permissions 	= auth_permissions($session->$fieldRole);
				auth_set($session);
				return $session;				
			}			
		}else{
			return $auth;
		}
		return false;
	}
}
/**
 * Get the namespace for the authentication object
 * Sometimes, the Auth session name should be different upon directory (namespace)
 */
function auth_namespace(){
	if(LC_NAMESPACE) $name = 'AuthUser.' . LC_NAMESPACE;
	else $name = 'AuthUser.default';
	return $name;	
}
/**
 * Get the authenticate user object from session
 */
function auth_get(){
	return session_get(auth_namespace(), true);
}
/**
 * Set the authenticate user object from session
 */
function auth_set($sess){
	session_set(auth_namespace(), $sess, true);	
}
/**
 * Clear the authenticate user object from session
 */
function auth_clear(){
	global $_auth;
	deleteSession(auth_namespace());
	$_auth = NULL;
}
/**
 * Check if a user is not authenticated
 */
function auth_isAnonymous(){
	global $lc_auth;
	$session = auth_get();
	$field = $lc_auth['fields']['id'];	
	if(is_object($session) && $session->$field > 0) return false;
	else return true;
}
/**
 * Check if a user is authenticated
 * @return boolean
 */
function auth_isLoggedIn(){
	return ! auth_isAnonymous();
}

if(!function_exists('auth_role')){
/**
 * Check if the authenticate user has the specific user role
 * This function is overwritable from the custom helpers/auth_helper.php  
 * @param  string $role The user role name or key
 * @return boolean
 */
	function auth_role($role){
		global $lc_auth;
		if(auth_isAnonymous()) return false;
		$session = auth_get();
		$field = $lc_auth['fields']['role'];
		if($session->$field == $role) return true;
		return false;
	}
}

if(!function_exists('auth_permissions')){
/**
 * Get the permissions of a particular role
 * This function is overwritable from the custom helpers/auth_helper.php
 * @param  string $role The user role name or key
 * @return Array of permissions of the role   
 */
	function auth_permissions($role){
		global $lc_auth;
		$perms = $lc_auth['perms'];
		return (isset($perms[$role])) ? $perms[$role] : array();
	}
}

if(!function_exists('auth_access')){
/**
 * Check if the authenticate uses has a particular permission
 * This function is overwritable from the custom helpers/auth_helper.php 
 * @param  string $perm The permission key
 * @return boolean  
 */
	function auth_access($perm){
		if(auth_isAnonymous()) return false;
		$sess = auth_get();
		if(in_array($perm, $sess->permissions)){
			return true;
		}
		return false;
	}
}