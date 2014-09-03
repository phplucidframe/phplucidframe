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
 
/** @type array It contains the built and executed queries through out the script execuation */
global $db_builtQueries;
$db_builtQueries = array();

/**
 * @access private
 * Return the database configuration of the given namespace
 * @param string $namespace Namespace of the configuration.
 */
function db_config($namespace='default'){
	if(!isset($GLOBALS['lc_databases'][$namespace])){
		die('Database configuration error!');
	}
	return $GLOBALS['lc_databases'][$namespace];
} 
/**
 * Return the database host name of the given namespace
 * @param string $namespace Namespace of the configuration. 
 */ 
function db_host($namespace='default'){
	$conf = db_config($namespace);
	return $conf['host'];
}
/**
 * Return the database name of the given namespace
 * @param string $namespace Namespace of the configuration.
 */ 
function db_name($namespace='default'){
	$conf = db_config($namespace);
	if(!isset($conf['database'])) die('Database name is not set.');
	return $conf['database'];
}
/**
 * Return the database user name of the given namespace
 */ 
function db_user($namespace='default'){
	$conf = db_config($namespace);
	if(!isset($conf['username'])) die('Database username is not set.');
	return $conf['username'];
}
/**
 * Return the database table prefix of the given namespace
 * @param string $namespace Namespace of the configuration.
 */ 
function db_prefix($namespace='default'){
	$conf = $GLOBALS['lc_databases'][$namespace];
	return isset($conf['prefix']) ? $conf['prefix'] : '';
}
/**
 * Return the database collation of the given namespace
 * @param string $namespace Namespace of the configuration.
 */ 
function db_collation($namespace='default'){
	$conf = db_config($namespace);
	return isset($conf['collation']) ? $conf['collation'] : '';
}
/**
 * Check and get the database configuration settings
 */
function db_prerequisite($namespace='default'){	
	if(db_host($namespace) && db_user($namespace) && db_name($namespace)){
		return db_config($namespace);
	}else{
		$error = new stdClass();
		$error->message = array(_t('Required to configure $lc_databases in "/inc/config.php". It is not allowed to configure in the application-level file "/app/inc/site.config.php".'));
		$error->type 	= 'sitewide-message error';		
		include( _i('inc/site.error.php') );
		exit;
	}
}
/**
 * Establish a new database connection to the MySQL server
 * @param string $namespace Namespace of the configuration.
 */ 
function db_connect($namespace='default'){
	global $_conn;
	global $_db;
	$conf = db_config($namespace);
	# Connection
	$_conn = mysqli_connect($conf['host'], $conf['username'], $conf['password']);
	if (!$_conn) {
		die('Not connected mysqli!');
	}
    # Force MySQL to use the UTF-8 character set. Also set the collation, if a certain one has been set; 
	# otherwise, MySQL defaults to 'utf8_general_ci' # for UTF-8.		
	if (!db_setCharset('utf8')){
		printf("Error loading character set utf8: %s", mysqli_error($_conn));
	}
	if(db_collation()){
		db_query('SET NAMES utf8 COLLATE ' . db_collation());
	}
	# Select DB
	$_db = mysqli_select_db($_conn, $conf['database']);
	if (!$_db) {
		die('Can\'t use  : ' . $conf['database'] .' - '. mysqli_error($_conn));
	}
}
/**
 * Sets the default client character set
 *
 * @param  string $charset The charset to be set as default.
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function db_setCharset($charset){
	global $_conn;
	return mysqli_set_charset( $_conn , $charset );
}
/**
 * Perform a query on the database
 *
 * @param string $sql SQL query string
 * @param array $args Array of placeholders and their values
 *		array(
 *			':placeholder1' => $value1,
 *			':placeholder2' => $value2
 *		)
 *
 * @return boolean Returns TRUE on success or FALSE on failure 
 */
function db_query($sql, $args=array()){
	global $_conn;
	global $db_builtQueries;
	
	if(count($args)){
		foreach($args as $key => $value){
			if(is_array($value)){
				$value = array_map('db_escapeStringMulti', $value);
				$value = implode(',', $value);
				$regex = '/'.$key.'\b/i';
				$sql = preg_replace($regex, $value, $sql);				
			}else{
				$regex = '/'.$key.'\b/i';
				$sql = preg_replace($regex, db_escapeString($value), $sql);
			} 
		}		
	}
	
	$db_builtQueries[] = $sql;
	
	if($result = mysqli_query($_conn, $sql)){
		return $result;
	}else{
		echo 'Invalid query: ' . db_error();
		return false;
	}	
}
/**
 * Get the last executed SQL string or one of the executed SQL strings by prividing the index
 *
 * @param  int The index number of the query returned; if not given, the last query is returned
 * @return string Return the built and executed SQL string
 */
function db_queryStr(){
	global $db_builtQueries;
	$arg = func_get_args();
	$index = (count($arg) == 0) ? count($db_builtQueries)-1 : 0;
	return (isset($db_builtQueries[$index])) ? $db_builtQueries[$index] : '';
}
/**
 * Escapes special characters in a string for use in a SQL statement, taking into account the current charset of the connection 
 *
 * @param  string $str An escaped string
 * @return string
 */
function db_escapeString($str){
	global $_conn;
	if(get_magic_quotes_gpc()){
		return mysqli_real_escape_string($_conn, stripslashes($str));
	}else{
		return mysqli_real_escape_string($_conn, $str);
	}	
}
/**
 * @access privte
 * Quote and return the value if it is string; otherwise return as it is
 * This is used for array_map()
 */
function db_escapeStringMulti($val){
	$val = db_escapeString($val);
	return is_numeric($val) ? $val : '"'.$val.'"';
}
/**
 * Returns a string description of the last error
 * @return string
 */
function db_error(){
	global $_conn;
	return mysqli_error($_conn);
}
/**
 * Returns the error code for the most recent MySQLi function call
 * @return int
 */
function db_errorNo(){
	global $_conn;
	return mysqli_errno($_conn);
}
/**
 * Gets the number of rows in a result
 * @param  MySQLi result resource
 * @return int Returns the number of rows in the result set.
 */
function db_numRows($result){
	return mysqli_num_rows($result);
}
/**
 * Fetch a result row as an associative, a numeric array, or both
 * @param  MySQLi result resource
 * @return An array that corresponds to the fetched row or NULL if there are no more rows for the resultset represented by the result parameter. 
 */
function db_fetchArray($result){
	return mysqli_fetch_array($result);
}
/**
 * Fetch a result row as an associative array
 * @param  MySQLi result resource
 * @return An associative array that corresponds to the fetched row or NULL if there are no more rows. 
 */
function db_fetchAssoc($result){
	return mysqli_fetch_assoc($result);
}
/**
 * Returns the current row of a result set as an object
 * @param  MySQLi result resource
 * @return an object that corresponds to the fetched row or NULL if there are no more rows in resultset. 
 */
function db_fetchObject($result){
	return mysqli_fetch_object($result);
}
/**
 * Returns the auto generated id used in the last query
 * @return  The value of the AUTO_INCREMENT field that was updated by the previous query. 
 *			Returns zero if there was no previous query on the connection or if the query did not update an AUTO_INCREMENT value. 
 */
function db_insertId(){
	global $_conn;
	return mysqli_insert_id($_conn);
}
/**
 * Returns the generated slug used in the last query
 * @return string 
 */
function db_insertSlug(){
	return session_get('lastInsertSlug');
}
/**
 * Perform a count query on the database and return the count
 *
 * @param string $sql The SQL query string
 * @param array $args The array of placeholders and their values
 *		array(
 *			':placeholder1' => $value1,
 *			':placeholder2' => $value2
 *		)
 *
 * @return int The result count
 */
function db_count($sql, $args=array()){
	if($result = db_fetch($sql, $args)){
		return $result;
	}
	return 0;	
}
/**
 * Perform a query on the database and return the first field value only.
 * It adds the LIMIT 1 clause if the query has no record limit
 * This will be useful for COUNT(), MAX(), MIN() queries
 *
 * @param string $sql The SQL query string
 * @param array $args The array of placeholders and their values
 *		array(
 *			':placeholder1' => $value1,
 *			':placeholder2' => $value2
 *		)
 *
 * @return mixed The value of the first field
 */
function db_fetch($sql, $args=array()){
	if( ! preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql) ){
		$sql .= ' LIMIT 1';
	}
	if($result = db_query($sql, $args)){
		if($row = db_fetchArray($result)){
			return $row[0];
		}
	}
	return false;	
}
/**
 * Perform a query on the database and return the first result row as object
 * It adds the LIMIT 1 clause if the query has no record limit 
 * This is useful for one-row fetching. No need explicit db_query() call as this invokes it internally.
 *
 * @param string $sql The SQL query string
 * @param array $args The array of placeholders and their values
 *		array(
 *			':placeholder1' => $value1,
 *			':placeholder2' => $value2
 *		)
 *
 * @return object The result object
 */
function db_fetchResult($sql, $args=array()){
	if( ! preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql) ){
		$sql .= ' LIMIT 1';
	}	
	if($result = db_query($sql, $args)){
		if($row = db_fetchObject($result)){
			return $row;
		}
	}
	return false;	
}
/**
 * Handy MYSQL insert operation
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 *					array(
 *						'fieldNameToSlug' => $valueToSlug, # if $lc_useDBAutoFields is enabled
 *						'fieldName1' => $fieldValue1,
 *						'fieldName2' => $fieldValue2
 *					)
 * $param boolean $useSlug True to include the slug field or False to not exclude it 
 * @return boolean Returns TRUE on success or FALSE on failure 
 */
if(!function_exists('db_insert')){
	function db_insert($table, $data=array(), $useSlug=true){
		if(count($data) == 0) return;
		global $_conn;
		global $lc_useDBAutoFields;
		
		$table = ltrim($table, db_prefix());
		$table = db_prefix().$table;
		
		# Invoke the hook db_insert_[table_name] if any
		$hook = 'db_insert_' . strtolower($table);
		if(function_exists($hook)){
			return call_user_func_array( $hook, array($table, $data, $useSlug) );
		}
		
		# if slug is already provided in the data array, use it
		if(array_key_exists( 'slug', $data )){
			$slug = db_escapeString($data['slug']);
			$slug = _slug($slug);
			$data['slug'] = $slug;
			session_set('lastInsertSlug', $slug);			
			$useSlug = false;
		}
				
		$fields = array_keys($data);
		$data   = array_values($data);
		if($lc_useDBAutoFields){
			if($useSlug) $fields[] = 'slug';
			$fields[] = 'created';
			$fields[] = 'updated';
		}
		$sqlFields = implode(', ', $fields);
		$values = array();
		$i = 0;		
		
		# escape the data
		foreach($data as $val){
			if($i == 0 && $useSlug){ 
				$slug = db_escapeString($val);
			}
			if(is_null($val)) $values[] = 'NULL';
			else $values[] = '"'.db_escapeString($val).'"';
			$i++;
		}
		if($lc_useDBAutoFields){
			if($useSlug){ 
				$slug = _slug($slug, $table);
				session_set('lastInsertSlug', $slug);				
				$values[] = '"'.$slug.'"';
			}
			$values[] = '"'.date('Y-m-d H:i:s').'"';
			$values[] = '"'.date('Y-m-d H:i:s').'"';
		}	
		$sqlValues = implode(', ', $values);
		
		$sql = 'INSERT INTO '.$table.' ('.$sqlFields.')
				VALUES ( '.$sqlValues.' )';
		return db_query($sql);
	}
}
/**
 * Handy MYSQL update operation
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 *								array(
 *									'pkFieldName' 		=> $pkFieldValue, <=== 0
 *									'fieldNameToSlug' 	=> $valueToSlug,  <=== 1 # if $lc_useDBAutoFields is enabled
 *									'fieldName1' 		=> $value1,
 *									'fieldName2' 		=> $value2
 *								)
 * $param boolean $useSlug True to include the slug field or False to not exclude it
 * $param array $addtionalCondition The addtional conditions for the UPDATE query, for example,
 *								array(
 *									'fieldName1' 		=> $value1,
 *									'fieldName2' 		=> $value2
 *								) 
 * @return boolean Returns TRUE on success or FALSE on failure 
 */
if(!function_exists('db_update')){
	function db_update($table, $data=array(), $useSlug=true, $additionalCondition=array()){
		if(count($data) == 0) return;
		global $_conn;
		global $lc_useDBAutoFields;
		
		$table 	= ltrim($table, db_prefix());
		$table 	= db_prefix().$table;
		
		# Invoke the hook db_update_[table_name] if any
		$hook = 'db_update_' . strtolower($table);
		if(function_exists($hook)){
			return call_user_func_array( $hook, array($table, $data, $useSlug, $additionalCondition) );
		}

		# if slug is already provided in the data array, use it
		if(array_key_exists( 'slug', $data )){
			$slug = db_escapeString($data['slug']);
			$slug = _slug($slug);
			$data['slug'] = $slug;
			session_set('lastInsertSlug', $slug);			
			$useSlug = false;
		}
						
		$fields = array();
		$slug 	= '';
		$condition = '';
		$notCondition = '';
		$i = 0;
		foreach($data as $field => $value){
			if($i == 0){ # $data[0] is PK
				$condition = array($field => db_escapeString($value)); # for PK condition
				$notCondition = array("$field !=" => db_escapeString($value));
			}else{	
				if(is_null($value)) $fields[] = $field . ' = NULL';		
				else $fields[] = $field . ' = "' . db_escapeString($value) . '"';
			}
			if($i == 1 && $useSlug == true){ # $data[1] is slug
				$slug = db_escapeString($value);
			}
			$i++;
		}
		
		if($condition){ # must have condition
			if(count($additionalCondition)){
				$condition = array_merge($condition, $additionalCondition);
			}
			if($lc_useDBAutoFields){
				if($useSlug){
					$slug = _slug($slug, $table, $notCondition);
					session_set('lastInsertSlug', $slug);			
					$fields[] = 'slug = "'.$slug.'"';
				}
				$fields[] = 'updated = "' . date('Y-m-d H:i:s') . '"';
			}
			$fields = implode(', ', $fields);
			
			if($condition) $condition = db_condition($condition);			
					
			$sql = 'UPDATE ' . $table . ' SET ' . $fields . ' 
					WHERE ' . $condition . ' LIMIT 1';
			return db_query($sql);
		}else{
			return false;
		}
	}
}
/**
 * Handy MYSQL delete operation for single record. 
 * It checks FK delete RESTRICT constraint, then SET deleted if it cannot be deleted
 *
 * @param string $table Table name without prefix
 * @param array $cond The array of condition for delete - field names and values, for example
 *		array(
 *			'fieldName1' 	=> $value1,
 *			'fieldName2 >=' => $value2,
 *			'fieldName3 	=> NULL
 *		)
 * @return boolean Returns TRUE on success or FALSE on failure 
 */
if(!function_exists('db_delete')){
	function db_delete($table, $cond=array()){
		$table = ltrim($table, db_prefix());
		$table = db_prefix().$table;

		# Invoke the hook db_delete_[table_name] if any
		$hook = 'db_delete_' . strtolower($table);
		if(function_exists($hook)){
			return call_user_func_array( $hook, array($table, $cond) );
		}
				
		$condition = db_condition($cond);
		if($condition) $condition = ' WHERE '.$condition;
		
		ob_start(); # to capture error return
		$sql = 'DELETE FROM '.$table.' '.$condition.' LIMIT 1';
		db_query($sql);
		$return = ob_get_clean();
		if($return){
			if(db_errorNo() == 1451){ # If there is FK delete RESTRICT constraint
				$sql = 'UPDATE '.$table.' SET deleted = "'.date('Y-m-d H:i:s').'" '.$condition.' LIMIT 1';
				return (db_query($sql)) ? true : false;
			}else{
				echo $return;
				return false;
			}
		}
		if(db_errorNo() == 0) return true;
		else return false;
	}
}
/**
 * Handy MYSQL delete operation for multiple records
 *
 * @param string $table Table name without prefix
 * @param array $cond Array of condition for delete - field names and values, for example
 *			array(
 *				'fieldName1' 	=> $value1,
 *				'fieldName2 >=' => $value2,
 *				'fieldName3' 	=> NULL
 *			)
 *
 * @return boolean Returns TRUE on success or FALSE on failure 
 */
if(!function_exists('db_delete_multi')){
	function db_delete_multi($table, $cond=array()){
		$table = ltrim($table, db_prefix());
		$table = db_prefix().$table;

		# Invoke the hook db_delete_[table_name] if any
		$hook = 'db_delete_multi_' . strtolower($table);
		if(function_exists($hook)){
			return call_user_func_array( $hook, array($table, $cond) );
		}
		
		$condition = db_condition($cond);
		if($condition) $condition = ' WHERE '.$condition;
						
		ob_start(); # to capture error return
		$sql = 'DELETE FROM ' . $table . $condition;		
		db_query($sql);
		$return = ob_get_clean();

		if($return && db_errorNo() > 0){ # if there is any error
			return false;
		}
		if(db_errorNo() == 0) return true;
		else return false;
	}
}
/**
 * Build the SQL WHERE clause from the various condition arrays
 *
 * @param array $cond The condition array, for example
 *			array(
 *				'fieldName1' 	=> $value1,
 *				'fieldName2 >='	=> $value2, <=== operators allowed =, >=, <=, >, <, !=, <>
 *				'fieldName3 	=> NULL
 *			)
 * @param string $type The condition type "AND" or "OR"; Default is "AND"
 *
 * @return string The built condition WHERE clause
 */
function db_condition($cond=array(), $type='AND'){
	if(!is_array($cond)) return '';	
	$type 		= strtoupper($type);
	$condition 	= array();
	$operators 	= array('=', '>=', '<=', '>', '<', '!=', '<>');
	$opr 		= '=';
	foreach($cond as $field => $value){
		$field = trim($field);
		$regexp = '/^[a-z0-9_]+(\.)?[a-z0-9_]+(\s)*('.implode('|', $operators).'){1}$/i';
		# check if any operator is given in the field
		if(preg_match($regexp, $field, $matches)){
			$opr 	= $matches[3];
			$field 	= trim(str_replace($opr, '', $field));
		}			
		if(is_string($value)) $condition[] = $field . ' ' . $opr . ' "' . db_escapeString($value) . '"';
		elseif(is_null($value)) $condition[] = $field . ' IS NULL';
		else $condition[] = $field . ' ' . $opr . ' ' . db_escapeString($value);
	}
	if(count($condition)) $condition = implode(" {$type} ", $condition);
	else $condition = '';
	return $condition;
}
/**
 * Build the SQL WHERE clause AND condition from the various condition arrays
 *
 * @param array $cond The condition array, for example
 *			array(
 *				'fieldName1' 	=> $value1,
 *				'fieldName2 >='	=> $value2, <=== operators allowed =, >=, <=, >, <, !=, <>
 *				'fieldName3 	=> NULL
 *			)
 * @return string The built condition WHERE clause
 */
function db_conditionAND($cond=array()){
	return db_condition($cond, 'AND');
}
/**
 * Build the SQL WHERE clause OR condition from the various condition arrays
 *
 * @param array $cond The condition array, for example
 *			array(
 *				'fieldName1' 	=> $value1,
 *				'fieldName2 >='	=> $value2, <=== operators allowed =, >=, <=, >, <, !=, <>
 *				'fieldName3 	=> NULL
 *			)
 * @return string The built condition WHERE clause
 */
function db_conditionOR($cond=array()){
	return db_condition($cond, 'OR');
}
/**
 * Build the SQL expression like SUM, MAX, AVG, etc
 *
 * @return array The condition array, for example
 *			array(
 *				'value'  => $value,
 *				'exp >=' => $exp, 
 *				'field 	 => $field
 *			)
 */
function db_exp($value, $exp=''){
	if($exp) $field = strtoupper($field) . '(' . $value . ')';
	else $field = '';
	return array(
		'value' => $value,
		'exp' => $exp,
		'field' => $field
	);
}